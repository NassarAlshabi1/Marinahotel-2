<?php
/**
 * Optimized Booking List Page
 * Uses the new QueryOptimizer class for better performance
 */

include_once '../../includes/db.php';
include_once '../../includes/query_optimizer.php';
include_once '../../includes/header.php';

// Initialize query optimizer
$optimizer = new QueryOptimizer($conn);

// Record page load start time for performance monitoring
$start_time = microtime(true);

if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Get status filter
$status_filter = $_GET['status'] ?? null;

// Get optimized booking list
$bookings = $optimizer->getOptimizedBookingList($status_filter);

// Get active alerts separately for better performance
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

// Record page load time
$load_time = microtime(true) - $start_time;
$optimizer->recordMetric('page_load_time', $load_time, 'bookings/list_optimized.php');
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قائمة الحجوزات المحسنة - فندق مارينا بلازا</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/css/fontawesome.min.css" rel="stylesheet">
    <style>
        .container {
            max-width: 1200px;
        }

        .table {
            font-size: 14px;
            width: 100% !important;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table thead th {
            text-align: center;
            vertical-align: middle;
            padding: 15px 10px;
            font-weight: bold;
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table tbody td {
            text-align: center;
            vertical-align: middle;
            padding: 12px 8px;
            border-bottom: 1px solid #dee2e6;
        }

        .table tbody tr:hover {
            background-color: #f5f5f5;
        }

        .alert-badge {
            position: relative;
            display: inline-block;
        }

        .alert-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .status-occupied {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-vacant {
            background-color: #d1edff;
            color: #0c5460;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            margin: 1px;
        }

        .remaining-positive {
            color: #dc3545;
            font-weight: bold;
        }

        .remaining-zero {
            color: #28a745;
            font-weight: bold;
        }

        .remaining-negative {
            color: #007bff;
            font-weight: bold;
        }

        .performance-info {
            position: fixed;
            bottom: 10px;
            left: 10px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            z-index: 1000;
        }

        .filter-bar {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .stats-cards {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .stat-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            flex: 1;
            min-width: 150px;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }

        .stat-label {
            font-size: 14px;
            color: #6c757d;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-list"></i> قائمة الحجوزات المحسنة</h2>
            <div>
                <a href="add.php" class="btn btn-success">
                    <i class="fas fa-plus"></i> حجز جديد
                </a>
                <a href="list.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> النسخة العادية
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <?php
        $stats = $optimizer->getDashboardStats();
        ?>
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['occupied_rooms'] ?? 0; ?></div>
                <div class="stat-label">غرف محجوزة</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['available_rooms'] ?? 0; ?></div>
                <div class="stat-label">غرف شاغرة</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['today_checkins'] ?? 0; ?></div>
                <div class="stat-label">وصول اليوم</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['today_revenue'] ?? 0); ?></div>
                <div class="stat-label">إيراد اليوم</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['high_alerts'] ?? 0; ?></div>
                <div class="stat-label">تنبيهات عالية</div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <label for="statusFilter" class="form-label">تصفية حسب الحالة:</label>
                    <select id="statusFilter" class="form-select" onchange="filterBookings()">
                        <option value="">جميع الحجوزات</option>
                        <option value="محجوزة" <?php echo $status_filter == 'محجوزة' ? 'selected' : ''; ?>>محجوزة</option>
                        <option value="شاغرة" <?php echo $status_filter == 'شاغرة' ? 'selected' : ''; ?>>شاغرة</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="searchInput" class="form-label">البحث:</label>
                    <input type="text" id="searchInput" class="form-control" placeholder="ابحث بالاسم أو رقم الغرفة أو الهاتف" onkeyup="searchBookings()">
                </div>
            </div>
        </div>

        <!-- Active Alerts -->
        <?php if (!empty($active_alerts)): ?>
        <div class="alert alert-warning">
            <h5><i class="fas fa-exclamation-triangle"></i> التنبيهات النشطة</h5>
            <?php foreach ($active_alerts as $booking_id => $alerts): ?>
                <?php foreach ($alerts as $alert): ?>
                    <div class="alert alert-<?php echo $alert['alert_type'] == 'high' ? 'danger' : ($alert['alert_type'] == 'medium' ? 'warning' : 'info'); ?> alert-dismissible fade show">
                        <strong>غرفة <?php echo htmlspecialchars($alert['room_number']); ?> - <?php echo htmlspecialchars($alert['guest_name']); ?>:</strong>
                        <?php echo htmlspecialchars($alert['note_text']); ?>
                        <small class="text-muted">(<?php echo date('d/m/Y H:i', strtotime($alert['created_at'])); ?>)</small>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Bookings Table -->
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="bookingsTable">
                <thead>
                    <tr>
                        <th>رقم الحجز</th>
                        <th>اسم النزيل</th>
                        <th>رقم الغرفة</th>
                        <th>نوع الغرفة</th>
                        <th>تاريخ الوصول</th>
                        <th>عدد الليالي</th>
                        <th>المبلغ المطلوب</th>
                        <th>المبلغ المدفوع</th>
                        <th>المبلغ المتبقي</th>
                        <th>الحالة</th>
                        <th>التنبيهات</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="12" class="text-center text-muted">لا توجد حجوزات</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $booking): ?>
                            <tr class="booking-row" 
                                data-guest-name="<?php echo htmlspecialchars($booking['guest_name']); ?>"
                                data-room-number="<?php echo htmlspecialchars($booking['room_number']); ?>"
                                data-phone="<?php echo htmlspecialchars($booking['guest_phone']); ?>"
                                data-status="<?php echo htmlspecialchars($booking['status']); ?>">
                                <td><?php echo htmlspecialchars($booking['booking_id']); ?></td>
                                <td><?php echo htmlspecialchars($booking['guest_name']); ?></td>
                                <td>
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($booking['room_number']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($booking['room_type']); ?></td>
                                <td><?php echo htmlspecialchars($booking['checkin_date']); ?></td>
                                <td>
                                    <span class="badge bg-info"><?php echo $booking['calculated_nights']; ?></span>
                                </td>
                                <td><?php echo number_format($booking['room_price'] * $booking['calculated_nights']); ?></td>
                                <td><?php echo number_format($booking['paid_amount']); ?></td>
                                <td>
                                    <?php 
                                    $remaining = $booking['remaining_amount'];
                                    $class = $remaining > 0 ? 'remaining-positive' : ($remaining == 0 ? 'remaining-zero' : 'remaining-negative');
                                    ?>
                                    <span class="<?php echo $class; ?>">
                                        <?php echo number_format($remaining); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $booking['status'] == 'محجوزة' ? 'bg-warning' : 'bg-success'; ?>">
                                        <?php echo htmlspecialchars($booking['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($booking['has_alerts'] > 0): ?>
                                        <div class="alert-badge">
                                            <i class="fas fa-exclamation-triangle text-danger"></i>
                                            <span class="alert-count"><?php echo $booking['has_alerts']; ?></span>
                                        </div>
                                    <?php else: ?>
                                        <i class="fas fa-check-circle text-success"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="view.php?id=<?php echo $booking['booking_id']; ?>" 
                                           class="btn btn-info btn-sm" title="عرض">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $booking['booking_id']; ?>" 
                                           class="btn btn-warning btn-sm" title="تعديل">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="payment.php?booking_id=<?php echo $booking['booking_id']; ?>" 
                                           class="btn btn-success btn-sm" title="دفع">
                                            <i class="fas fa-money-bill"></i>
                                        </a>
                                        <a href="add_note.php?booking_id=<?php echo $booking['booking_id']; ?>" 
                                           class="btn btn-secondary btn-sm" title="إضافة ملاحظة">
                                            <i class="fas fa-sticky-note"></i>
                                        </a>
                                        <?php if ($booking['status'] == 'محجوزة'): ?>
                                            <a href="checkout.php?id=<?php echo $booking['booking_id']; ?>" 
                                               class="btn btn-danger btn-sm" title="مغادرة">
                                                <i class="fas fa-sign-out-alt"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Performance Info -->
        <div class="performance-info">
            تم تحميل <?php echo count($bookings); ?> حجز في <?php echo number_format($load_time * 1000, 2); ?> مللي ثانية
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterBookings() {
            const status = document.getElementById('statusFilter').value;
            const url = new URL(window.location);
            if (status) {
                url.searchParams.set('status', status);
            } else {
                url.searchParams.delete('status');
            }
            window.location = url;
        }

        function searchBookings() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('.booking-row');
            
            rows.forEach(row => {
                const guestName = row.dataset.guestName.toLowerCase();
                const roomNumber = row.dataset.roomNumber.toLowerCase();
                const phone = row.dataset.phone.toLowerCase();
                
                const matches = guestName.includes(searchTerm) || 
                               roomNumber.includes(searchTerm) || 
                               phone.includes(searchTerm);
                
                row.style.display = matches ? '' : 'none';
            });
        }

        // Auto-refresh every 5 minutes
        setTimeout(() => {
            window.location.reload();
        }, 300000);
    </script>
</body>
</html>