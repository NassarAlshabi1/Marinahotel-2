<?php
session_start();
include_once '../../includes/db.php';

if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// معالجة البحث
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = '';
if (!empty($search)) {
    $search_condition = "AND (b.guest_name LIKE ? OR b.room_number LIKE ? OR b.guest_phone LIKE ?)";
}

// استعلام لجلب الحجوزات النشطة مع الملاحظات والتنبيهات
$query = "
    SELECT 
        b.booking_id,
        b.guest_name,
        b.guest_phone,
        b.room_number,
        r.price AS room_price,
        DATE_FORMAT(b.checkin_date, '%d/%m/%Y') AS checkin_date,
        CASE 
            WHEN b.actual_checkout IS NULL 
            THEN DATEDIFF(CURRENT_DATE(), b.checkin_date) + 
                 (CASE WHEN TIME(CURRENT_TIME()) > '13:00:00' THEN 1 ELSE 0 END)
            ELSE DATEDIFF(b.actual_checkout, b.checkin_date)
        END AS nights,
        IFNULL((SELECT SUM(amount) FROM payment WHERE booking_id = b.booking_id), 0) AS paid_amount,
        (r.price * (
            CASE 
                WHEN b.actual_checkout IS NULL 
                THEN DATEDIFF(CURRENT_DATE(), b.checkin_date) + 
                     (CASE WHEN TIME(CURRENT_TIME()) > '13:00:00' THEN 1 ELSE 0 END)
                ELSE DATEDIFF(b.actual_checkout, b.checkin_date)
            END
        )) - IFNULL((SELECT SUM(amount) FROM payment WHERE booking_id = b.booking_id), 0) AS remaining_amount,
        b.status,
        b.notes,
        (SELECT COUNT(*) FROM booking_notes WHERE booking_id = b.booking_id AND is_active = 1 AND (alert_until IS NULL OR alert_until > NOW())) AS has_alerts
    FROM bookings b
    JOIN rooms r ON b.room_number = r.room_number
    WHERE b.status != 'غادر' AND b.actual_checkout IS NULL $search_condition
    ORDER BY b.checkin_date DESC
";

$stmt = $conn->prepare($query);
if (!empty($search)) {
    $search_param = "%$search%";
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
}
$stmt->execute();
$result = $stmt->get_result();

// استعلام لجلب التنبيهات النشطة
$alerts_query = "
    SELECT 
        bn.note_id,
        bn.booking_id,
        bn.note_text,
        bn.alert_type,
        bn.created_at,
        b.guest_name,
        b.room_number
    FROM booking_notes bn
    JOIN bookings b ON bn.booking_id = b.booking_id
    WHERE bn.is_active = 1 
    AND (bn.alert_until IS NULL OR bn.alert_until > NOW())
    AND b.status != 'غادر' AND b.actual_checkout IS NULL
    ORDER BY bn.created_at DESC
";

$alerts_result = $conn->query($alerts_query);
$active_alerts = [];
if ($alerts_result && $alerts_result->num_rows > 0) {
    while ($alert = $alerts_result->fetch_assoc()) {
        $active_alerts[$alert['booking_id']][] = $alert;
    }
}

include_once '../../includes/header.php';
?>

<style>
    /* أنماط خاصة بقائمة الحجوزات */
    .bookings-container {
        padding: 20px 0;
    }

    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 15px;
        margin-bottom: 30px;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .page-header h1 {
        margin: 0;
        font-size: 2rem;
        font-weight: 700;
    }

    .page-header p {
        margin: 10px 0 0;
        opacity: 0.9;
        font-size: 1.1rem;
    }

    /* أنماط البحث */
    .search-bar {
        background: white;
        padding: 20px;
        border-radius: 15px;
        margin-bottom: 25px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .search-input {
        border-radius: 10px;
        border: 2px solid #e9ecef;
        padding: 12px 15px;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .search-input:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
    }

    /* أنماط التنبيهات */
    .alerts-section {
        background: white;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .alerts-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e9ecef;
    }

    .alerts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 15px;
    }

    .alert-card {
        padding: 15px;
        border-radius: 10px;
        border-left: 4px solid;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }

    .alert-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    }

    .alert-card.high {
        border-left-color: #dc3545;
        background: linear-gradient(135deg, #fff 0%, #fff5f5 100%);
    }

    .alert-card.medium {
        border-left-color: #fd7e14;
        background: linear-gradient(135deg, #fff 0%, #fff8f0 100%);
    }

    .alert-card.low {
        border-left-color: #198754;
        background: linear-gradient(135deg, #fff 0%, #f0fff4 100%);
    }

    .alert-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .alert-guest {
        font-weight: 600;
        color: #2c3e50;
    }

    .alert-room {
        background: #e9ecef;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .alert-content {
        margin-bottom: 10px;
        line-height: 1.5;
        color: #495057;
    }

    .alert-time {
        font-size: 0.85rem;
        color: #6c757d;
        text-align: left;
    }

    /* أنماط الجدول */
    .table-container {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .table {
        font-size: 0.9rem;
        margin-bottom: 0;
    }

    .table thead th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-weight: 600;
        text-align: center;
        padding: 15px 10px;
        border: none;
        white-space: nowrap;
    }

    .table tbody td {
        padding: 12px 10px;
        vertical-align: middle;
        border-bottom: 1px solid #e9ecef;
    }

    .table tbody tr:hover {
        background-color: #f8f9fa;
    }

    /* أنماط الشارات والأزرار */
    .badge {
        font-size: 0.75rem;
        padding: 0.4em 0.8em;
        font-weight: 600;
        border-radius: 20px;
    }

    .btn {
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.3s ease;
        padding: 8px 12px;
        font-size: 0.85rem;
    }

    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .action-buttons {
        display: flex;
        gap: 5px;
        justify-content: center;
        flex-wrap: wrap;
    }

    /* أنماط التنبيهات في الجدول */
    .alert-badge {
        position: relative;
        display: inline-block;
        cursor: pointer;
    }

    .alert-icon {
        font-size: 1.2rem;
        transition: all 0.3s ease;
    }

    .alert-high { color: #dc3545; }
    .alert-medium { color: #fd7e14; }
    .alert-low { color: #198754; }

    .alert-count {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #dc3545;
        color: white;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        font-size: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }

    /* أنماط responsive */
    @media (max-width: 768px) {
        .table {
            font-size: 0.8rem;
        }
        
        .table thead th,
        .table tbody td {
            padding: 8px 5px;
        }
        
        .action-buttons {
            flex-direction: column;
        }
        
        .alerts-grid {
            grid-template-columns: 1fr;
        }
        
        .btn {
            font-size: 0.75rem;
            padding: 6px 10px;
        }
    }

    /* إخفاء الأعمدة في الأجهزة الصغيرة */
    @media (max-width: 576px) {
        .table .d-none.d-md-table-cell {
            display: none !important;
        }
    }
</style>

<div class="bookings-container">
    <div class="container">
        <!-- عنوان الصفحة -->
        <div class="page-header">
            <h1><i class="fas fa-list me-3"></i>قائمة الحجوزات الحالية</h1>
            <p>إدارة جميع الحجوزات النشطة في الفندق</p>
        </div>

        <!-- أزرار التنقل -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="../dash.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-right me-2"></i>العودة إلى لوحة التحكم
            </a>
            <a href="add2.php" class="btn btn-success">
                <i class="fas fa-plus-circle me-2"></i>حجز جديد
            </a>
        </div>

        <!-- شريط البحث -->
        <div class="search-bar">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-md-8">
                    <input type="text" name="search" class="form-control search-input" 
                           placeholder="البحث بالاسم أو رقم الغرفة أو رقم الهاتف..." 
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-4">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="fas fa-search me-2"></i>بحث
                        </button>
                        <?php if (!empty($search)): ?>
                        <a href="list.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <!-- قسم التنبيهات النشطة -->
        <?php 
        $total_alerts = 0;
        foreach ($active_alerts as $booking_alerts) {
            $total_alerts += count($booking_alerts);
        }
        
        if ($total_alerts > 0): 
        ?>
        <div class="alerts-section">
            <div class="alerts-title">
                <i class="fas fa-bell me-2 text-warning"></i>التنبيهات النشطة 
                <span class="badge bg-danger ms-2"><?= $total_alerts ?></span>
            </div>
            <div class="alerts-grid">
                <?php foreach ($active_alerts as $booking_id => $booking_alerts): ?>
                    <?php foreach ($booking_alerts as $alert): ?>
                        <div class="alert-card <?= $alert['alert_type'] ?>">
                            <div class="alert-header">
                                <span class="alert-guest">
                                    <i class="fas fa-user me-1"></i><?= htmlspecialchars($alert['guest_name']) ?>
                                </span>
                                <span class="alert-room">
                                    <i class="fas fa-door-open me-1"></i>غرفة <?= htmlspecialchars($alert['room_number']) ?>
                                </span>
                            </div>
                            <div class="alert-content">
                                <?= htmlspecialchars($alert['note_text']) ?>
                            </div>
                            <div class="alert-time">
                                <i class="fas fa-clock me-1"></i><?= date('d/m/Y H:i', strtotime($alert['created_at'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- جدول الحجوزات -->
        <div class="table-container">
            <?php if (!empty($search)): ?>
            <div class="alert alert-info mb-3">
                <i class="fas fa-info-circle me-2"></i>
                نتائج البحث عن: <strong><?= htmlspecialchars($search) ?></strong>
                <?php if ($result->num_rows == 0): ?>
                - لم يتم العثور على نتائج
                <?php else: ?>
                - تم العثور على <?= $result->num_rows ?> نتيجة
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>اسم النزيل</th>
                            <th>رقم الغرفة</th>
                            <th class="d-none d-md-table-cell">السعر/ليلة</th>
                            <th class="d-none d-lg-table-cell">تاريخ الوصول</th>
                            <th>عدد الليالي</th>
                            <th>المدفوع</th>
                            <th>المتبقي</th>
                            <th>حالة الفاتورة</th>
                            <th class="d-none d-md-table-cell">حالة الحجز</th>
                            <th>تنبيهات</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php $counter = 1; ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <?php
                                    $nights = max(1, $row['nights']);
                                    $total_price = $row['room_price'] * $nights;
                                    $remaining = max(0, $row['remaining_amount']);
                                    $paid = $row['paid_amount'];

                                    if ($paid >= $total_price) {
                                        $payment_status = 'مسددة';
                                        $payment_class = 'bg-success text-white';
                                    } elseif ($paid > 0) {
                                        $payment_status = 'مدفوعة جزئياً';
                                        $payment_class = 'bg-warning text-dark';
                                    } else {
                                        $payment_status = 'غير مسددة';
                                        $payment_class = 'bg-danger text-white';
                                    }

                                    $booking_status_labels = [
                                        'confirmed' => 'مؤكدة',
                                        'checked_in' => 'مقيمة حالياً',
                                        'checked_out' => 'مغادرة',
                                        'cancelled' => 'ملغية',
                                        'محجوزة' => 'محجوزة'
                                    ];
                                    $booking_status = $booking_status_labels[$row['status']] ?? htmlspecialchars($row['status']);
                                    
                                    // تحديد نوع التنبيه الأعلى أهمية
                                    $alert_type = '';
                                    if (isset($active_alerts[$row['booking_id']])) {
                                        foreach ($active_alerts[$row['booking_id']] as $alert) {
                                            if ($alert['alert_type'] == 'high') {
                                                $alert_type = 'high';
                                                break;
                                            } elseif ($alert['alert_type'] == 'medium' && $alert_type != 'high') {
                                                $alert_type = 'medium';
                                            } elseif ($alert['alert_type'] == 'low' && $alert_type == '') {
                                                $alert_type = 'low';
                                            }
                                        }
                                    }
                                ?>
                                <tr>
                                    <td class="text-center fw-bold"><?= $counter++; ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($row['guest_name']); ?></td>
                                    <td class="text-center fw-bold"><?= htmlspecialchars($row['room_number']); ?></td>
                                    <td class="text-center d-none d-md-table-cell"><?= number_format($row['room_price'], 0); ?></td>
                                    <td class="text-center d-none d-lg-table-cell"><?= htmlspecialchars($row['checkin_date']); ?></td>
                                    <td class="text-center fw-bold"><?= $nights; ?></td>
                                    <td class="text-center text-success fw-bold"><?= number_format($paid, 0); ?></td>
                                    <td class="text-center <?= $remaining > 0 ? 'text-danger' : 'text-success' ?> fw-bold">
                                        <?= number_format($remaining, 0); ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?= $payment_class; ?>"><?= $payment_status; ?></span>
                                    </td>
                                    <td class="text-center d-none d-md-table-cell fw-bold"><?= $booking_status; ?></td>
                                    <td class="text-center">
                                        <?php if (isset($active_alerts[$row['booking_id']])): ?>
                                            <div class="alert-badge" title="عرض التنبيهات">
                                                <i class="fas fa-bell alert-icon alert-<?= $alert_type ?>"></i>
                                                <span class="alert-count"><?= count($active_alerts[$row['booking_id']]); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <a href="add_note.php?id=<?= $row['booking_id']; ?>" 
                                               class="btn btn-sm btn-outline-secondary" title="إضافة تنبيه">
                                                <i class="fas fa-plus"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="action-buttons">
                                            <a href="payment.php?id=<?= $row['booking_id']; ?>" 
                                               class="btn btn-sm btn-success" title="إدارة المدفوعات">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </a>
                                            <?php if (isset($active_alerts[$row['booking_id']])): ?>
                                            <a href="add_note.php?id=<?= $row['booking_id']; ?>" 
                                               class="btn btn-sm btn-warning" title="إدارة التنبيهات">
                                                <i class="fas fa-bell"></i>
                                            </a>
                                            <?php endif; ?>
                                            <a href="edit.php?id=<?= $row['booking_id']; ?>" 
                                               class="btn btn-sm btn-primary" title="تعديل الحجز">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="12" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                        <h5>لا توجد حجوزات</h5>
                                        <p>لا توجد حجوزات نشطة في الوقت الحالي</p>
                                        <a href="add2.php" class="btn btn-primary">
                                            <i class="fas fa-plus-circle me-2"></i>إضافة حجز جديد
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- إحصائيات سريعة -->
        <?php if ($result && $result->num_rows > 0): ?>
        <div class="mt-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="bg-primary text-white p-3 rounded text-center">
                        <h5><?= $result->num_rows ?></h5>
                        <small>إجمالي الحجوزات النشطة</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="bg-warning text-dark p-3 rounded text-center">
                        <h5><?= $total_alerts ?></h5>
                        <small>التنبيهات النشطة</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="bg-success text-white p-3 rounded text-center">
                        <?php
                        mysqli_data_seek($result, 0);
                        $paid_count = 0;
                        while ($row = mysqli_fetch_assoc($result)) {
                            $nights = max(1, $row['nights']);
                            $total_price = $row['room_price'] * $nights;
                            if ($row['paid_amount'] >= $total_price) {
                                $paid_count++;
                            }
                        }
                        ?>
                        <h5><?= $paid_count ?></h5>
                        <small>فواتير مسددة بالكامل</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="bg-danger text-white p-3 rounded text-center">
                        <h5><?= $result->num_rows - $paid_count ?></h5>
                        <small>فواتير غير مسددة</small>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// تحسين تجربة المستخدم
document.addEventListener('DOMContentLoaded', function() {
    // إضافة tooltips للأزرار
    const buttons = document.querySelectorAll('[title]');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // تحسين البحث - البحث أثناء الكتابة (debounced)
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (this.value.length >= 2 || this.value.length === 0) {
                    // يمكن إضافة البحث المباشر هنا إذا رغبت
                }
            }, 500);
        });
    }

    // تحديث البيانات تلقائياً كل 5 دقائق
    setInterval(() => {
        const currentUrl = new URL(window.location);
        if (!currentUrl.searchParams.get('search')) {
            location.reload();
        }
    }, 300000);
});
</script>

<?php include '../../includes/footer.php'; ?>
