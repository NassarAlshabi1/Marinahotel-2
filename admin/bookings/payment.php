<?php
require_once '../../includes/functions.php';
include '../../includes/db.php';      // يفترض أن هذا الملف يقوم بإنشاء اتصال $conn بقاعدة البيانات
include '../../includes/auth.php';    // للمصادقة فقط

// جلب رقم الحجز من الرابط
$booking_id = intval($_GET['id'] ?? 0);
if ($booking_id <= 0) {
    die("رقم الحجز غير صالح");
}

// جلب بيانات الحجز مع سعر الغرفة
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
    die("خطأ في الاستعلام: " . $conn->error);
}
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking_result = $stmt->get_result();
if ($booking_result->num_rows === 0) {
    die("الحجز غير موجود");
}
$booking = $booking_result->fetch_assoc();

// حساب عدد الليالي بين checkin_date و checkout_date
$checkin = new DateTime($booking['checkin_date']);
$checkout = new DateTime($booking['checkout_date']);
$nights = $checkout->diff($checkin)->days;
if ($nights < 1) $nights = 1;

// حساب المبلغ الإجمالي والمتبقي
$total_price = $booking['room_price'] * $nights;
$paid_amount = $booking['paid_amount'];
$remaining = max(0, $total_price - $paid_amount);

$payment_error = '';
$success_msg = '';

// معالجة تسجيل المغادرة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    if ($remaining == 0) {
        $conn->begin_transaction();
        try {
            // تحديث حالة الحجز إلى 'شاغرة' وتسجيل actual_checkout
            $update_booking = "UPDATE bookings SET status = 'شاغرة', actual_checkout = NOW() WHERE booking_id = ?";
            $stmt_update_booking = $conn->prepare($update_booking);
            $stmt_update_booking->bind_param("i", $booking_id);
            if (!$stmt_update_booking->execute()) {
                throw new Exception("خطأ في تحديث حالة الحجز: " . $stmt_update_booking->error);
            }

            // تحديث حالة الغرفة إلى 'شاغرة'
            $update_room = "UPDATE rooms SET status = 'شاغرة' WHERE room_number = ?";
            $stmt_update_room = $conn->prepare($update_room);
            $stmt_update_room->bind_param("s", $booking['room_number']);
            if (!$stmt_update_room->execute()) {
                throw new Exception("خطأ في تحديث حالة الغرفة: " . $stmt_update_room->error);
            }

            $conn->commit();

            header("Location: payment.php?id={$booking_id}&success=" . urlencode("تم تسجيل خروج النزيل بنجاح وتم تحرير الغرفة."));
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $payment_error = $e->getMessage();
        }
    } else {
        $payment_error = "لا يمكن تسجيل المغادرة قبل تسديد كافة المستحقات.";
    }
}

// معالجة تسجيل دفعة جديدة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment'])) {
    $amount = intval($_POST['amount'] ?? 0);
    $payment_date = $_POST['payment_date'] ?? date('Y-m-d H:i:s');
    $payment_method = $conn->real_escape_string($_POST['payment_method'] ?? 'نقدي');
    $notes = $conn->real_escape_string($_POST['notes'] ?? '');

    if ($amount <= 0 || $amount > $remaining) {
        $payment_error = "المبلغ يجب أن يكون بين 1 و {$remaining} ريال";
    } else {
        $insert_payment = "INSERT INTO payment (booking_id, amount, payment_date, payment_method, notes) VALUES (?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($insert_payment);
        if (!$stmt_insert) {
            $payment_error = "خطأ في تحضير الاستعلام: " . $conn->error;
        } else {
            $stmt_insert->bind_param("iisss", $booking_id, $amount, $payment_date, $payment_method, $notes);
            if ($stmt_insert->execute()) {
                // تحديث المبلغ المتبقي بعد الدفع الجديد
                $remaining_after_payment = $remaining - $amount;

                // إرسال رسالة واتساب للعميل
                $phone = $booking['guest_phone'];
                $message = "عزيزي {$booking['guest_name']}، تم استلام دفعة بقيمة: {$amount} ريال\nرقم الحجز: {$booking_id}\nالمبلغ المتبقي: {$remaining_after_payment} ريال\nشكراً لاختيارك فندقنا\nللاستفسار: 9677734587456";

                $wa_result = send_yemeni_whatsapp($phone, $message);

                $success_msg = "تم تسجيل الدفعة بنجاح";
                if (isset($wa_result['status']) && $wa_result['status'] === 'sent') {
                    $success_msg .= " وتم إرسال الإشعار للعميل عبر واتساب.";
                } else {
                    $success_msg .= " ولكن لم يتم إرسال الإشعار للعميل.";
                }

                header("Location: payment.php?id={$booking_id}&success=" . urlencode($success_msg));
                exit();
            } else {
                $payment_error = "خطأ في تسجيل الدفعة: " . $stmt_insert->error;
            }
        }
    }
}

// جلب سجل الدفعات السابقة
$payments_query = "SELECT * FROM payment WHERE booking_id = ? ORDER BY payment_date DESC";
$stmt_payments = $conn->prepare($payments_query);
$stmt_payments->bind_param("i", $booking_id);
$stmt_payments->execute();
$payments_result = $stmt_payments->get_result();

// تضمين الهيدر بعد انتهاء معالجة POST
include '../../includes/header.php';
?>

<style>
        .payment-container {
            max-width: 900px;
            margin: auto;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 30px 40px;
            box-sizing: border-box;
        }
        .back-button {
            display: inline-block;
            margin-bottom: 20px;
            padding: 8px 18px;
            background-color: #3498db;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        .back-button:hover {
            background-color: #2980b9;
        }
        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
            font-weight: 700;
        }
        table.info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            table-layout: fixed;
            word-wrap: break-word;
        }
        table.info-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 1.1em;
        }
        table.info-table td.label {
            font-weight: 600;
            color: #555;
            width: 180px;
            background-color: #f9fafb;
        }
        form {
            background-color: #f7f9fc;
            padding: 25px;
            border-radius: 10px;
            box-shadow: inset 0 0 10px #e0e0e0;
            margin-bottom: 40px;
            box-sizing: border-box;
        }
        form label {
            display: inline-block;
            width: 140px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #34495e;
        }
        form input[type="number"],
        form input[type="date"],
        form select,
        form textarea {
            width: calc(100% - 150px);
            padding: 10px 14px;
            border: 1.8px solid #ccc;
            border-radius: 8px;
            font-size: 1em;
            margin-bottom: 18px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        form input[type="number"]:focus,
        form input[type="date"]:focus,
        form select:focus,
        form textarea:focus {
            border-color: #2980b9;
            outline: none;
        }
        form textarea {
            resize: vertical;
            min-height: 60px;
        }
        button.submit-btn {
            background-color: #2980b9;
            color: white;
            padding: 14px 40px;
            border: none;
            border-radius: 10px;
            font-size: 1.2em;
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: block;
            margin: 0 auto;
            font-weight: 700;
        }
        button.submit-btn:hover {
            background-color: #1c5980;
        }
        button.submit-btn.checkout-btn {
            background-color: #27ae60;
        }
        button.submit-btn.checkout-btn:hover {
            background-color: #1e8449;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1.5px solid #c3e6cb;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 600;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1.5px solid #f5c6cb;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 600;
        }
        table.payments-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            table-layout: fixed;
            word-wrap: break-word;
        }
        table.payments-table th,
        table.payments-table td {
            padding: 12px 15px;
            border: 1px solid #ddd;
            text-align: center;
            font-size: 1em;
        }
        table.payments-table th {
            background-color: #2980b9;
            color: white;
            font-weight: 700;
        }
        table.payments-table tr:nth-child(even) {
            background-color: #f4f6f8;
        }
        .note {
            font-size: 0.9em;
            color: #666;
            margin-top: 20px;
            text-align: center;
        }
        /* --- استجابة للشاشات الصغيرة --- */
        @media (max-width: 900px) {
            .container {
                padding: 20px 25px;
            }
            form label {
                width: 130px;
            }
            form input[type="number"],
            form input[type="date"],
            form select,
            form textarea {
                width: calc(100% - 140px);
            }
        }
        @media (max-width: 700px) {
            form label {
                width: 120px;
            }
            form input[type="number"],
            form input[type="date"],
            form select,
            form textarea {
                width: calc(100% - 120px);
            }
        }
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            .container {
                max-width: 100%;
                padding: 15px 15px;
                border-radius: 0;
                box-shadow: none;
            }
            form label,
            form input[type="number"],
            form input[type="date"],
            form select,
            form textarea {
                width: 100% !important;
                display: block;
            }
            form label {
                margin-bottom: 6px;
            }
            button.submit-btn {
                width: 100%;
                padding: 14px 0;
            }
            table.info-table td.label {
                width: 40%;
                font-size: 1em;
                padding: 10px 8px;
            }
            table.info-table td {
                font-size: 1em;
                padding: 10px 8px;
            }
            table.payments-table th, table.payments-table td {
                font-size: 0.9em;
                padding: 8px 6px;
            }
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <div class="payment-container">
    <a href="list.php" class="back-button">&larr; العودة لقائمة الحجوزات</a>
    <h2>تسجيل دفعة - حجز رقم <?= htmlspecialchars($booking_id) ?></h2>
    <?php if (!empty($payment_error)): ?>
        <div class="error-message" style="color:#c0392b; background:#fbeee0; padding:10px; border-radius:6px; margin-bottom:15px; text-align:center; font-weight:bold;">
            <?= htmlspecialchars($payment_error) ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($_GET['success'])): ?>
        <div class="success-message" style="color:#27ae60; background:#eafaf1; padding:10px; border-radius:6px; margin-bottom:15px; text-align:center; font-weight:bold;">
            <?= htmlspecialchars($_GET['success']) ?>
        </div>
    <?php elseif (!empty($success_msg)): ?>
        <div class="success-message" style="color:#27ae60; background:#eafaf1; padding:10px; border-radius:6px; margin-bottom:15px; text-align:center; font-weight:bold;">
            <?= htmlspecialchars($success_msg) ?>
        </div>
    <?php endif; ?>

    <table class="info-table">
        <tr>
            <td class="label">اسم النزيل:</td>
            <td><?= htmlspecialchars($booking['guest_name']) ?></td>
        </tr>
        <tr>
            <td class="label">رقم الغرفة:</td>
            <td><?= htmlspecialchars($booking['room_number']) ?></td>
        </tr>
        <tr>
            <td class="label">تاريخ الوصول:</td>
            <td><?= date('d/m/Y', strtotime($booking['checkin_date'])) ?></td>
        </tr>
        <tr>
            <td class="label">تاريخ المغادرة:</td>
            <td><?= date('d/m/Y', strtotime($booking['checkout_date'])) ?></td>
        </tr>
        <tr>
            <td class="label">عدد الليالي:</td>
            <td><?= $nights ?></td>
        </tr>
        <tr>
            <td class="label">سعر الليلة:</td>
            <td><?= number_format($booking['room_price'], 0) ?> ريال</td>
        </tr>
        <tr>
            <td class="label">المبلغ الإجمالي:</td>
            <td><?= number_format($total_price, 0) ?> ريال</td>
        </tr>
        <tr>
            <td class="label">المبلغ المدفوع:</td>
            <td><?= number_format($paid_amount, 0) ?> ريال</td>
        </tr>
        <tr>
            <td class="label">المبلغ المتبقي:</td>
            <td><b><?= number_format($remaining, 0) ?> ريال</b></td>
        </tr>
    </table>

    <form method="post" novalidate>
        <label for="amount">المبلغ المدفوع:</label>
        <input type="number" id="amount" name="amount" min="1" max="<?= $remaining ?>" required>
        <br>

        <label for="payment_date">تاريخ الدفع:</label>
        <input type="date" id="payment_date" name="payment_date" value="<?= date('Y-m-d') ?>" required>
        <br>

        <label for="payment_method">طريقة الدفع:</label>
        <select id="payment_method" name="payment_method">
            <option value="نقدي">نقدي</option>
            <option value="تحويل">تحويل</option>
            <option value="بطاقة ائتمان">بطاقة ائتمان</option>
        </select>
        <br>

        <label for="notes">ملاحظات:</label>
        <textarea id="notes" name="notes" rows="2" placeholder="اختياري"></textarea>
        <br>

        <button type="submit" name="submit_payment" class="submit-btn">تسجيل الدفعة</button>
    </form>

    <?php if ($remaining == 0 && $booking['status'] == 'محجوزة'): ?>
        <form method="post" style="text-align: center; margin-top: 20px;">
            <button type="submit" name="checkout" class="submit-btn checkout-btn">
                تسجيل مغادرة النزيل
            </button>
        </form>
    <?php elseif ($booking['status'] == 'شاغرة'): ?>
        <p style="text-align:center; color: #27ae60; font-weight: bold; margin-top: 20px;">
            تم تسجيل خروج النزيل والغرفة متاحة الآن.
        </p>
    <?php endif; ?>

    <h3>سجل الدفعات السابقة</h3>
    <table class="payments-table">
        <thead>
        <tr>
            <th>#</th>
            <th>المبلغ</th>
            <th>التاريخ</th>
            <th>الطريقة</th>
            <th>ملاحظات</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($payments_result->num_rows > 0): ?>
            <?php while ($payment = $payments_result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($payment['payment_id']) ?></td>
                    <td><?= number_format($payment['amount'], 0) ?> ريال</td>
                    <td><?= date('d/m/Y H:i', strtotime($payment['payment_date'])) ?></td>
                    <td><?= htmlspecialchars($payment['payment_method']) ?></td>
                    <td><?= htmlspecialchars($payment['notes'] ?: '-') ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="5">لا توجد دفعات مسجلة لهذا الحجز</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <div class="note">عند تسجيل دفعة جديدة سيتم إرسال إشعار للعميل عبر واتساب تلقائياً.</div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
