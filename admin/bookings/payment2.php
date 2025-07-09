<?php
include '../../includes/db.php';
include '../../includes/header.php';

// التحقق من معرف الحجز
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../dashboard.php?error=معرف الحجز غير صالح");
    exit();
}

$booking_id = intval($_GET['id']);

// تسجيل المغادرة إذا تم الطلب
if (isset($_POST['checkout'])) {
    $now = date('Y-m-d H:i:s');
    $update_checkout_sql = "UPDATE bookings SET 
        actual_checkout = '$now',
        status = 'غادر',
        calculated_nights = 
            IF(TIME('$now') > '13:00:00', 
                DATEDIFF('$now', checkin_date) + 1,
                DATEDIFF('$now', checkin_date)
            )
        WHERE booking_id = $booking_id";

    if ($conn->query($update_checkout_sql)) {
        header("Location: payment.php?id=$booking_id&success=تم تسجيل المغادرة بنجاح");
        exit();
    } else {
        $error = "خطأ في تحديث المغادرة: " . $conn->error;
    }
}

// استعلام لجلب بيانات الحجز مع استخدام COUNT الليالي مثل استعلامك
$sql = "
SELECT 
    b.booking_id,
    b.guest_name,
    b.guest_id_number,
    b.room_number,
    r.type AS room_type,
    r.price AS room_price,
    DATE_FORMAT(b.checkin_date, '%d/%m/%Y') AS checkin_date,
    CASE 
        WHEN b.actual_checkout IS NULL THEN DATEDIFF(CURDATE(), b.checkin_date) + 1
        ELSE DATEDIFF(b.actual_checkout, b.checkin_date)
    END AS nights,
    b.status,
    b.notes,
    IFNULL((
        SELECT SUM(amount) FROM payment WHERE booking_id = b.booking_id
    ), 0) AS paid_amount
FROM bookings b
LEFT JOIN rooms r ON b.room_number = r.room_number
WHERE b.booking_id = $booking_id
LIMIT 1";

$result = $conn->query($sql);
if (!$result || $result->num_rows == 0) {
    header("Location: ../dashboard.php?error=الحجز غير موجود");
    exit();
}

$row = $result->fetch_assoc();

$nights = max(1, intval($row['nights']));
$total_price = $row['room_price'] * $nights;
$remaining = max(0, $total_price - $row['paid_amount']);

// جلب الدفعات
$payments_sql = "SELECT * FROM payment WHERE booking_id = $booking_id ORDER BY payment_date DESC";
$payments_res = $conn->query($payments_sql);

// معالجة إضافة دفعة جديدة
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_payment'])) {
    $amount = floatval($_POST['amount']);
    $payment_date = $conn->real_escape_string($_POST['payment_date']);
    $payment_method = $conn->real_escape_string($_POST['payment_method']);
    $notes = $conn->real_escape_string($_POST['notes']);

    if ($amount <= 0 || $amount > $remaining) {
        $error = "المبلغ يجب أن يكون بين 1 و " . number_format($remaining, 2) . " ر.س";
    } else {
        $insert_sql = "INSERT INTO payment (booking_id, amount, payment_date, payment_method, notes)
                       VALUES ($booking_id, $amount, '$payment_date', '$payment_method', '$notes')";
        if ($conn->query($insert_sql)) {
            header("Location: payment.php?id=$booking_id&success=تمت إضافة الدفعة بنجاح");
            exit();
        } else {
            $error = "خطأ في إضافة الدفعة: " . $conn->error;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إدارة الدفعات - حجز #<?= htmlspecialchars($booking_id) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
.checkout-btn {position: fixed; bottom: 20px; left: 20px; z-index: 1000;}
.back-btn {position: fixed; bottom: 20px; right: 20px; z-index: 1000;}
.highlight-row {background-color: #fff3cd;}
.total-row {font-weight: bold; background-color: #f8f9fa;}
</style>
</head>
<body>
<div class="container py-4">
<h1 class="text-center mb-4">إدارة الدفعات - حجز #<?= htmlspecialchars($booking_id) ?></h1>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-info-circle"></i> تفاصيل الحجز
            </div>
            <div class="card-body">
                <table class="table">
                    <tr><th width="40%">اسم النزيل:</th><td><?= htmlspecialchars($row['guest_name']); ?></td></tr>
                    <tr><th>رقم الغرفة:</th><td><?= htmlspecialchars($row['room_number']); ?></td></tr>
                    <tr><th>تاريخ الوصول:</th><td><?= htmlspecialchars($row['checkin_date']); ?></td></tr>
                    <tr><th>عدد الليالي:</th><td><?= $nights ?> ليلة</td></tr>
                    <tr><th>سعر الليلة:</th><td><?= number_format($row['room_price'], 2); ?> ر.س</td></tr>
                    <tr class="total-row"><th>المبلغ الإجمالي:</th><td><?= number_format($total_price, 1); ?> ر.س</td></tr>
                    <tr><th>المدفوع:</th><td><?= number_format($row['paid_amount'], 2); ?> ر.س</td></tr>
                    <tr class="total-row"><th>المتبقي:</th><td><?= number_format($remaining, 2); ?> ر.س</td></tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white"><i class="fas fa-money-bill-wave"></i> إضافة دفعة جديدة</div>
            <div class="card-body">
                <form method="post" novalidate>
                    <div class="mb-3">
                        <label for="amount" class="form-label">المبلغ (ر.س)</label>
                        <input type="number" name="amount" id="amount" class="form-control"
                               step="0.01" min="0.01" max="<?= $remaining ?>" value="<?= min($remaining, 1000) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="payment_date" class="form-label">تاريخ الدفع</label>
                        <input type="date" name="payment_date" id="payment_date" class="form-control"
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="payment_method" class="form-label">طريقة الدفع</label>
                        <select name="payment_method" id="payment_method" class="form-select" required>
                                                      <option value="نقدي">نقدي</option>
                            <option value="تحويل بنكي">تحويل بنكي</option>
                            <option value="بطاقة ائتمان">بطاقة ائتمان</option>
                            <option value="محفظة إلكترونية">محفظة إلكترونية</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">ملاحظات</label>
                        <textarea name="notes" id="notes" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" name="submit_payment" class="btn btn-success w-100">
                        <i class="fas fa-check-circle"></i> تسجيل الدفعة
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- سجل الدفعات -->
<div class="card shadow-sm mt-4">
    <div class="card-header bg-info text-white"><i class="fas fa-history"></i> سجل الدفعات</div>
    <div class="card-body">
        <?php if ($payments_res && $payments_res->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>المبلغ</th>
                        <th>التاريخ</th>
                        <th>طريقة الدفع</th>
                        <th>ملاحظات</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php while ($payment = $payments_res->fetch_assoc()): ?>
                        <tr>
                            <td><?= $payment['payment_id'] ?></td>
                            <td><?= number_format($payment['amount'], 2) ?> ر.س</td>
                            <td><?= date('d/m/Y H:i', strtotime($payment['payment_date'])) ?></td>
                            <td><?= htmlspecialchars($payment['payment_method']) ?></td>
                            <td><?= nl2br(htmlspecialchars($payment['notes'] ?: '-')) ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center">لا توجد دفعات مسجلة.</p>
        <?php endif; ?>
    </div>
</div>

<!-- زر العودة -->
<a href="../dashboard.php" class="btn btn-primary back-btn">
    <i class="fas fa-arrow-left"></i> العودة لرئيسية النظام
</a>

<!-- زر تسجيل المغادرة -->
<?php if ($row['status'] != 'غادر'): ?>
    <form method="post" class="checkout-btn" onsubmit="return confirm('هل أنت متأكد من تسجيل مغادرة النزيل؟');">
        <button type="submit" name="checkout" class="btn btn-danger btn-lg shadow">
            <i class="fas fa-sign-out-alt"></i> تسجيل مغادرة النزيل
        </button>
    </form>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>
</body>
</html>
