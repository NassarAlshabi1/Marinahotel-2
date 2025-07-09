<?php
ob_start();
session_start();
include '../../includes/db.php';
require_once '../../includes/functions.php';

// التحقق من معرف الحجز
$booking_id = intval($_GET['id'] ?? 0);
if ($booking_id <= 0) {
    $_SESSION['error'] = "رقم الحجز غير صالح";
    header("Location: list.php");
    exit();
}

$booking_query = "
    SELECT b.booking_id, b.guest_name, b.guest_phone, b.room_number, b.checkin_date, b.checkout_date, 
           r.price AS room_price,
           b.status,
           IFNULL((SELECT SUM(p.amount) FROM payment p WHERE p.booking_id = b.booking_id), 0) AS paid_amount
    FROM bookings b
    LEFT JOIN rooms r ON b.room_number = r.room_number
    WHERE b.booking_id = ? LIMIT 1
";

$stmt = $conn->prepare($booking_query);
if (!$stmt) {
    $_SESSION['error'] = "خطأ في الاستعلام: " . $conn->error;
    header("Location: list.php");
    exit();
}
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking_result = $stmt->get_result();
if ($booking_result->num_rows === 0) {
    $_SESSION['error'] = "الحجز غير موجود";
    header("Location: list.php");
    exit();
}
$booking = $booking_result->fetch_assoc();

$checkin = new DateTime($booking['checkin_date']);
$checkout = new DateTime($booking['checkout_date']);
$nights = $checkout->diff($checkin)->days;
if ($nights < 1) $nights = 1;

$total_price = $booking['room_price'] * $nights;
$paid_amount = $booking['paid_amount'];
$remaining = max(0, $total_price - $paid_amount);

// تسجيل المغادرة إذا تم الطلب
if (isset($_POST['checkout'])) {
    if ($remaining == 0) {
        $conn->begin_transaction();
        try {
            $update_booking = "UPDATE bookings SET status = 'شاغرة', actual_checkout = NOW() WHERE booking_id = ?";
            $stmt_update_booking = $conn->prepare($update_booking);
            $stmt_update_booking->bind_param("i", $booking_id);
            if (!$stmt_update_booking->execute()) {
                throw new Exception("خطأ في تحديث حالة الحجز: " . $stmt_update_booking->error);
            }

            $update_room = "UPDATE rooms SET status = 'شاغرة' WHERE room_number = ?";
            $stmt_update_room = $conn->prepare($update_room);
            $stmt_update_room->bind_param("s", $booking['room_number']);
            if (!$stmt_update_room->execute()) {
                throw new Exception("خطأ في تحديث حالة الغرفة: " . $stmt_update_room->error);
            }

            // إرسال رسالة شكر عند المغادرة
            $checkout_message = "شكراً لاختيارك فندق مارينا 🏨\n";
            $checkout_message .= "تم تسجيل خروجك بنجاح\n";
            $checkout_message .= "نتطلع لاستقبالك مرة أخرى 🌟\n";
            $checkout_message .= "للحجوزات: 967734587456";
            
            send_yemeni_whatsapp($booking['guest_phone'], $checkout_message, $booking_id);
            
            // إنشاء إشعار نظام
            create_system_notification(
                "تسجيل مغادرة",
                "تم تسجيل خروج النزيل {$booking['guest_name']} من الغرفة {$booking['room_number']}",
                "success"
            );

            $conn->commit();
            $_SESSION['success'] = "تم تسجيل خروج النزيل بنجاح وتم تحرير الغرفة.";
            header("Location: payment.php?id=$booking_id");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "لا يمكن تسجيل المغادرة قبل تسديد كافة المستحقات.";
    }
}

// جلب الدفعات
$payments_query = "SELECT * FROM payment WHERE booking_id = ? ORDER BY payment_date DESC";
$stmt_payments = $conn->prepare($payments_query);
$stmt_payments->bind_param("i", $booking_id);
$stmt_payments->execute();
$payments_res = $stmt_payments->get_result();

// معالجة إضافة دفعة جديدة
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_payment'])) {
    $amount = floatval($_POST['amount']);
    $payment_date = $_POST['payment_date'] ?? date('Y-m-d H:i:s');
    $payment_method = $conn->real_escape_string($_POST['payment_method'] ?? 'نقدي');
    $notes = $conn->real_escape_string($_POST['notes'] ?? '');

    if ($amount <= 0 || $amount > $remaining) {
        $_SESSION['error'] = "المبلغ يجب أن يكون بين 1 و " . number_format($remaining, 0) . " ريال";
    } else {
        $insert_sql = "INSERT INTO payment (booking_id, amount, payment_date, payment_method, notes)
                       VALUES (?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($insert_sql);
        $stmt_insert->bind_param("idsss", $booking_id, $amount, $payment_date, $payment_method, $notes);
        
        if ($stmt_insert->execute()) {
            $remaining_after_payment = $remaining - $amount;
            $phone = $booking['guest_phone'];
            
            // تنسيق رسالة أفضل
            $message = "🏨 فندق مارينا - إيصال دفع\n";
            $message .= "══════════════════════\n";
            $message .= "عزيزي/ة: {$booking['guest_name']}\n";
            $message .= "تم استلام دفعة: " . number_format($amount, 0) . " ريال 💰\n";
            $message .= "رقم الحجز: #{$booking_id}\n";
            $message .= "الغرفة: {$booking['room_number']}\n";
            $message .= "طريقة الدفع: {$payment_method}\n";
            $message .= "══════════════════════\n";
            $message .= "المبلغ الإجمالي: " . number_format($total_price, 0) . " ريال\n";
            $message .= "المدفوع: " . number_format($paid_amount + $amount, 0) . " ريال\n";
            $message .= "المتبقي: " . number_format($remaining_after_payment, 0) . " ريال\n";
            
            if ($remaining_after_payment == 0) {
                $message .= "\n✅ تم تسديد كامل المبلغ\n";
                $message .= "شكراً لتعاملك معنا 🌟";
            } else {
                $message .= "\n⏰ يرجى تسديد المتبقي";
            }
            
            $message .= "\n\nللاستفسار: 967734587456\nشكراً لاختيارك فندقنا 🙏";

            $wa_result = send_yemeni_whatsapp($phone, $message, $booking_id);
            
            // إنشاء إشعار نظام
            create_system_notification(
                "دفعة جديدة",
                "تم استلام دفعة قدرها " . number_format($amount, 0) . " ريال من {$booking['guest_name']}",
                "success"
            );
            
            // تحسين معالجة نتيجة إرسال الواتساب
            $wa_status = '';
            if (is_array($wa_result)) {
                switch ($wa_result['status']) {
                    case 'sent':
                        $wa_status = " وتم إرسال الإشعار للعميل عبر واتساب بنجاح.";
                        break;
                    case 'saved':
                        $wa_status = " وتم حفظ الإشعار للإرسال عند توفر الإنترنت.";
                        break;
                    default:
                        $wa_status = " ولكن لم يتم إرسال الإشعار للعميل.";
                }
            } else {
                $wa_status = " ولكن لم يتم إرسال الإشعار للعميل.";
            }

            $_SESSION['success'] = "تم تسجيل الدفعة بنجاح" . $wa_status;
            header("Location: payment.php?id=$booking_id");
            exit();
        } else {
            $_SESSION['error'] = "خطأ في إضافة الدفعة: " . $conn->error;
        }
    }
}

// إعادة جلب البيانات بعد أي تحديث
$stmt = $conn->prepare($booking_query);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking_result = $stmt->get_result();
$booking = $booking_result->fetch_assoc();
$paid_amount = $booking['paid_amount'];
$remaining = max(0, $total_price - $paid_amount);

// إعادة جلب الدفعات
$stmt_payments = $conn->prepare($payments_query);
$stmt_payments->bind_param("i", $booking_id);
$stmt_payments->execute();
$payments_res = $stmt_payments->get_result();
?>

<?php include '../../includes/header.php'; ?>

<div class="container py-4">
    <div class="payment-header">
        <h1><i class="fas fa-credit-card"></i> إدارة المدفوعات</h1>
        <div class="booking-id">حجز رقم <?= htmlspecialchars($booking_id) ?> - <?= htmlspecialchars($booking['guest_name']) ?></div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-info-circle"></i> تفاصيل الحجز
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr><th width="40%">اسم النزيل:</th><td><?= htmlspecialchars($booking['guest_name']); ?></td></tr>
                        <tr><th>رقم الهاتف:</th><td><?= htmlspecialchars($booking['guest_phone']); ?></td></tr>
                        <tr><th>رقم الغرفة:</th><td><?= htmlspecialchars($booking['room_number']); ?></td></tr>
                        <tr><th>تاريخ الوصول:</th><td><?= date('d/m/Y', strtotime($booking['checkin_date'])); ?></td></tr>
                        <tr><th>تاريخ المغادرة:</th><td><?= date('d/m/Y', strtotime($booking['checkout_date'])); ?></td></tr>
                        <tr><th>عدد الليالي:</th><td><?= $nights ?> ليلة</td></tr>
                        <tr><th>سعر الليلة:</th><td><?= number_format($booking['room_price'], 0); ?> ريال</td></tr>
                        <tr class="total-row"><th>المبلغ الإجمالي:</th><td><?= number_format($total_price, 0); ?> ريال</td></tr>
                        <tr><th>المدفوع:</th><td class="text-success"><?= number_format($paid_amount, 0); ?> ريال</td></tr>
                        <tr class="total-row <?= $remaining > 0 ? 'text-danger' : 'text-success' ?>">
                            <th>المتبقي:</th>
                            <td><?= number_format($remaining, 0); ?> ريال</td>
                        </tr>
                        <tr><th>حالة الحجز:</th>
                            <td>
                                <span class="badge" style="background: <?= $booking['status'] == 'محجوزة' ? '#28a745' : '#17a2b8' ?>;">
                                    <?= htmlspecialchars($booking['status']) ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <i class="fas fa-money-bill-wave"></i> إضافة دفعة جديدة
                </div>
                <div class="card-body">
                    <?php if ($remaining > 0): ?>
                    <form method="post" novalidate id="paymentForm">
                        <div class="mb-3">
                            <label for="amount" class="form-label">المبلغ (ريال)</label>
                            <input type="number" name="amount" id="amount" class="form-control"
                                   step="0.01" min="0.01" max="<?= $remaining ?>" value="<?= min($remaining, 1000) ?>" required>
                            <div class="form-text">الحد الأقصى: <?= number_format($remaining, 0) ?> ريال</div>
                        </div>
                        <div class="mb-3">
                            <label for="payment_date" class="form-label">تاريخ ووقت الدفع</label>
                            <input type="datetime-local" name="payment_date" id="payment_date" class="form-control"
                                   value="<?= date('Y-m-d\TH:i') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="payment_method" class="form-label">طريقة الدفع</label>
                            <select name="payment_method" id="payment_method" class="form-select" required>
                                <option value="نقدي">💵 نقدي</option>
                                <option value="تحويل بنكي">🏦 تحويل حوالة</option>
                                <option value="بطاقة ائتمان">💳 بطاقة ائتمان</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">ملاحظات</label>
                            <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="أضف أي ملاحظات إضافية..."></textarea>
                        </div>
                        <button type="submit" name="submit_payment" class="btn btn-success w-100" id="submitPaymentBtn">
                            <i class="fas fa-check-circle"></i> تسجيل الدفعة وإرسال إشعار واتساب
                        </button>
                    </form>
                    <?php else: ?>
                        <div class="alert alert-success text-center">
                            <i class="fas fa-check-circle fa-2x mb-2"></i>
                            <h5>تم تسديد كامل المبلغ المستحق</h5>
                            <p class="mb-0">يمكنك الآن تسجيل مغادرة النزيل</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- سجل الدفعات -->
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-info text-white">
            <i class="fas fa-history"></i> سجل الدفعات
        </div>
        <div class="card-body">
            <?php if ($payments_res && $payments_res->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>المبلغ</th>
                            <th>التاريخ والوقت</th>
                            <th>طريقة الدفع</th>
                            <th>ملاحظات</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php while ($payment = $payments_res->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= $payment['payment_id'] ?></td>
                                <td><strong class="text-success"><?= number_format($payment['amount'], 0) ?> ريال</strong></td>
                                <td><?= date('d/m/Y - H:i', strtotime($payment['payment_date'])) ?></td>
                                <td>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($payment['payment_method']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($payment['notes'] ?: 'لا توجد ملاحظات') ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center text-muted">
                    <i class="fas fa-receipt fa-3x mb-3"></i>
                    <p>لا توجد دفعات مسجلة حتى الآن.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- زر العودة -->
    <a href="../dash.php" class="btn btn-primary back-btn">
        <i class="fas fa-arrow-left"></i> العودة لرئيسية النظام
    </a>

    <!-- زر تسجيل المغادرة -->
    <?php if ($remaining == 0 && $booking['status'] == 'محجوزة'): ?>
        <form method="post" class="checkout-btn" id="checkoutForm">
            <button type="submit" name="checkout" class="btn btn-danger btn-lg shadow" onclick="return confirmCheckout()">
                <i class="fas fa-sign-out-alt"></i> تسجيل مغادرة النزيل وتحرير الغرفة
            </button>
        </form>
    <?php elseif ($booking['status'] == 'شاغرة'): ?>
        <div class="alert alert-info text-center mt-3">
            <i class="fas fa-info-circle"></i> تم تسجيل خروج النزيل بنجاح والغرفة متاحة الآن للحجوزات الجديدة
        </div>
    <?php endif; ?>
</div>

<style>
    .payment-header {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        padding: 25px 15px;
        border-radius: 12px;
        margin-bottom: 20px;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .payment-header h1 {
        margin: 0;
        font-size: 1.8rem;
        font-weight: 700;
    }
    .booking-id {
        font-size: 1.1rem;
        margin-top: 10px;
        opacity: 0.9;
    }
    
    .checkout-btn {position: fixed; bottom: 20px; left: 20px; z-index: 1000;}
    .back-btn {position: fixed; bottom: 20px; right: 20px; z-index: 1000;}
    .highlight-row {background-color: #fff3cd;}
    .total-row {font-weight: bold; background-color: #f8f9fa;}
    .badge {padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;}
    
    input[type="number"] {
        font-family: Arial, sans-serif;
        direction: ltr;
        text-align: left;
    }
    
    /* تحسين زر الإرسال */
    #submitPaymentBtn:disabled {
        background-color: #6c757d;
        border-color: #6c757d;
        opacity: 0.65;
    }
    
    .btn-loading {
        position: relative;
    }
    
    .btn-loading:after {
        content: "";
        position: absolute;
        width: 16px;
        height: 16px;
        top: 50%;
        left: 50%;
        margin-left: -8px;
        margin-top: -8px;
        border-radius: 50%;
        border: 2px solid #ffffff;
        border-color: #ffffff transparent #ffffff transparent;
        animation: btn-loading-spinner 1.2s linear infinite;
    }
    
    @keyframes btn-loading-spinner {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }
</style>

<!-- JavaScript محلي -->
<script src="../../assets/js/jquery.min.js"></script>
<script src="../../assets/js/bootstrap-full.js"></script>
<script src="../../assets/js/sweetalert2.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // إضافة معالج للنموذج
    const paymentForm = document.getElementById('paymentForm');
    const submitBtn = document.getElementById('submitPaymentBtn');
    
    if (paymentForm && submitBtn) {
        paymentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // التحقق من صحة البيانات
            const amount = parseFloat(document.getElementById('amount').value);
            const maxAmount = <?= $remaining ?>;
            
            if (amount <= 0 || amount > maxAmount) {
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ في المبلغ',
                    text: `المبلغ يجب أن يكون بين 1 و ${maxAmount.toLocaleString()} ريال`,
                    confirmButtonText: 'موافق'
                });
                return;
            }
            
            // تأكيد العملية
            Swal.fire({
                title: 'تأكيد الدفعة',
                text: `هل أنت متأكد من تسجيل دفعة قدرها ${amount.toLocaleString()} ريال؟`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#dc3545',
                confirmButtonText: 'نعم، تسجيل الدفعة',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    // تعطيل الزر وإظهار التحميل
                    submitBtn.disabled = true;
                    submitBtn.classList.add('btn-loading');
                    submitBtn.innerHTML = '<span style="opacity: 0;">تجاري التسجيل...</span>';
                    
                    // إرسال النموذج
                    paymentForm.submit();
                }
            });
        });
    }
});

function confirmCheckout() {
    return confirm('هل أنت متأكد من تسجيل مغادرة النزيل وتحرير الغرفة؟\nهذا الإجراء لا يمكن التراجع عنه.');
}

// تحسين تجربة المستخدم
$(document).ready(function() {
    // تنسيق المبلغ أثناء الكتابة
    $('#amount').on('input', function() {
        const value = parseFloat(this.value);
        const max = <?= $remaining ?>;
        
        if (value > max) {
            $(this).addClass('is-invalid');
            $(this).siblings('.form-text').addClass('text-danger').text(`المبلغ يتجاوز الحد الأقصى: ${max.toLocaleString()} ريال`);
        } else {
            $(this).removeClass('is-invalid');
            $(this).siblings('.form-text').removeClass('text-danger').text(`الحد الأقصى: ${max.toLocaleString()} ريال`);
        }
    });
    
    // تحديث الوقت تلقائياً
    setInterval(function() {
        const now = new Date();
        const isoString = now.getFullYear() + '-' + 
                          String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                          String(now.getDate()).padStart(2, '0') + 'T' + 
                          String(now.getHours()).padStart(2, '0') + ':' + 
                          String(now.getMinutes()).padStart(2, '0');
        $('#payment_date').val(isoString);
    }, 60000); // كل دقيقة
});
</script>

<?php include '../../includes/footer.php'; ?>