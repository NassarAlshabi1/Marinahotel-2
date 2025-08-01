<?php
/**
 * Optimized Dashboard with Caching
 * Uses the new caching system for better performance
 */

include_once '../includes/db.php';
include_once '../includes/cache_manager.php';
include_once '../includes/query_optimizer.php';
include_once '../includes/header.php';

// Initialize systems
$cache = getCache();
$optimizer = new QueryOptimizer($conn);

// Record page load start time
$start_time = microtime(true);

// Get dashboard statistics with caching
$stats = $cache->remember('dashboard_stats', function() use ($conn) {
    $query = "
        SELECT 
            (SELECT COUNT(*) FROM bookings WHERE status = 'محجوزة' AND actual_checkout IS NULL) as occupied_rooms,
            (SELECT COUNT(*) FROM rooms WHERE status = 'شاغرة') as available_rooms,
            (SELECT COUNT(*) FROM bookings WHERE DATE(checkin_date) = CURDATE()) as today_checkins,
            (SELECT COALESCE(SUM(amount), 0) FROM payment WHERE DATE(payment_date) = CURDATE()) as today_revenue,
            (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE date = CURDATE()) as today_expenses,
            (SELECT COUNT(*) FROM booking_notes WHERE is_active = 1 AND alert_type = 'high') as high_alerts,
            (SELECT COUNT(*) FROM bookings WHERE DATE(checkin_date) = CURDATE() + INTERVAL 1 DAY) as tomorrow_checkins,
            (SELECT COUNT(*) FROM bookings WHERE status = 'محجوزة' AND DATEDIFF(CURDATE(), checkin_date) > 7) as long_stays
    ";
    
    $result = $conn->query($query);
    return $result ? $result->fetch_assoc() : [];
}, 120); // Cache for 2 minutes

// Get recent activities with caching
$recent_activities = $cache->remember('recent_activities', function() use ($conn) {
    $query = "
        SELECT 'booking' as type, booking_id as id, guest_name as title, 
               DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') as time, 'success' as status
        FROM bookings 
        WHERE created_at >= NOW() - INTERVAL 24 HOUR
        
        UNION ALL
        
        SELECT 'payment' as type, payment_id as id, 
               CONCAT('دفعة بمبلغ ', amount, ' ريال') as title,
               DATE_FORMAT(payment_date, '%d/%m/%Y %H:%i') as time, 'info' as status
        FROM payment 
        WHERE payment_date >= NOW() - INTERVAL 24 HOUR
        
        UNION ALL
        
        SELECT 'expense' as type, id, 
               CONCAT('مصروف: ', description) as title,
               DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') as time, 'warning' as status
        FROM expenses 
        WHERE created_at >= NOW() - INTERVAL 24 HOUR
        
        ORDER BY time DESC
        LIMIT 10
    ";
    
    $result = $conn->query($query);
    $activities = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $activities[] = $row;
        }
    }
    return $activities;
}, 300); // Cache for 5 minutes

// Get room status summary with caching
$room_status = $cache->remember('room_status_summary', function() use ($conn) {
    $query = "
        SELECT 
            status,
            COUNT(*) as count,
            GROUP_CONCAT(room_number ORDER BY room_number) as rooms
        FROM rooms 
        GROUP BY status
    ";
    
    $result = $conn->query($query);
    $status_data = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $status_data[$row['status']] = $row;
        }
    }
    return $status_data;
}, 180); // Cache for 3 minutes

// Get financial summary for the current month
$financial_summary = $cache->remember('monthly_financial_summary', function() use ($conn) {
    $query = "
        SELECT 
            (SELECT COALESCE(SUM(amount), 0) FROM payment 
             WHERE MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())) as monthly_revenue,
            (SELECT COALESCE(SUM(amount), 0) FROM expenses 
             WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())) as monthly_expenses,
            (SELECT COALESCE(SUM(amount), 0) FROM payment 
             WHERE WEEK(payment_date) = WEEK(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())) as weekly_revenue,
            (SELECT COALESCE(SUM(amount), 0) FROM expenses 
             WHERE WEEK(date) = WEEK(CURDATE()) AND YEAR(date) = YEAR(CURDATE())) as weekly_expenses
    ";
    
    $result = $conn->query($query);
    return $result ? $result->fetch_assoc() : [];
}, 600); // Cache for 10 minutes

// Calculate page load time
$load_time = microtime(true) - $start_time;
$optimizer->recordMetric('dashboard_load_time', $load_time, 'dash_optimized.php');
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم المحسنة - فندق مارينا بلازا</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/fontawesome.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .dashboard-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #6c757d;
            font-size: 1rem;
            margin-bottom: 5px;
        }

        .stat-change {
            font-size: 0.9rem;
            font-weight: 500;
        }

        .stat-positive { color: #28a745; }
        .stat-negative { color: #dc3545; }
        .stat-neutral { color: #6c757d; }

        .activity-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 15px;
            font-size: 16px;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 500;
            margin-bottom: 2px;
        }

        .activity-time {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .room-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
            gap: 8px;
            margin-top: 15px;
        }

        .room-item {
            padding: 8px;
            text-align: center;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .room-occupied {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .room-vacant {
            background-color: #d1edff;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .performance-badge {
            position: fixed;
            bottom: 20px;
            left: 20px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 12px;
            z-index: 1000;
        }

        .quick-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .quick-action-btn {
            flex: 1;
            min-width: 120px;
            padding: 12px;
            text-align: center;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }

        .quick-action-btn:hover {
            transform: translateY(-1px);
            text-decoration: none;
        }

        .chart-container {
            position: relative;
            height: 250px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-tachometer-alt"></i> لوحة التحكم المحسنة</h2>
                <small class="text-muted">مرحباً <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'المستخدم'); ?></small>
            </div>
            <div>
                <a href="performance_dashboard.php" class="btn btn-info btn-sm">
                    <i class="fas fa-chart-line"></i> مراقبة الأداء
                </a>
                <a href="dash.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> النسخة العادية
                </a>
                <button class="btn btn-primary btn-sm" onclick="location.reload()">
                    <i class="fas fa-sync"></i> تحديث
                </button>
            </div>
        </div>

        <!-- Main Statistics -->
        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="dashboard-card text-center">
                    <div class="stat-number text-warning">
                        <?php echo $stats['occupied_rooms'] ?? 0; ?>
                    </div>
                    <div class="stat-label">غرف محجوزة</div>
                    <div class="stat-change stat-neutral">
                        من إجمالي <?php echo ($stats['occupied_rooms'] ?? 0) + ($stats['available_rooms'] ?? 0); ?> غرفة
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="dashboard-card text-center">
                    <div class="stat-number text-success">
                        <?php echo $stats['available_rooms'] ?? 0; ?>
                    </div>
                    <div class="stat-label">غرف شاغرة</div>
                    <div class="stat-change stat-positive">
                        متاحة للحجز
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="dashboard-card text-center">
                    <div class="stat-number text-info">
                        <?php echo number_format($stats['today_revenue'] ?? 0); ?>
                    </div>
                    <div class="stat-label">إيراد اليوم (ريال)</div>
                    <div class="stat-change stat-neutral">
                        مصروفات: <?php echo number_format($stats['today_expenses'] ?? 0); ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="dashboard-card text-center">
                    <div class="stat-number text-primary">
                        <?php echo $stats['today_checkins'] ?? 0; ?>
                    </div>
                    <div class="stat-label">وصول اليوم</div>
                    <div class="stat-change stat-neutral">
                        غداً: <?php echo $stats['tomorrow_checkins'] ?? 0; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Secondary Statistics -->
        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="dashboard-card text-center">
                    <div class="stat-number text-danger">
                        <?php echo $stats['high_alerts'] ?? 0; ?>
                    </div>
                    <div class="stat-label">تنبيهات عالية</div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="dashboard-card text-center">
                    <div class="stat-number text-warning">
                        <?php echo $stats['long_stays'] ?? 0; ?>
                    </div>
                    <div class="stat-label">إقامات طويلة</div>
                    <small class="text-muted">أكثر من 7 أيام</small>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="dashboard-card text-center">
                    <div class="stat-number text-success">
                        <?php echo number_format($financial_summary['weekly_revenue'] ?? 0); ?>
                    </div>
                    <div class="stat-label">إيراد الأسبوع</div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="dashboard-card text-center">
                    <div class="stat-number text-info">
                        <?php echo number_format($financial_summary['monthly_revenue'] ?? 0); ?>
                    </div>
                    <div class="stat-label">إيراد الشهر</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <h5><i class="fas fa-bolt"></i> إجراءات سريعة</h5>
                    <div class="quick-actions">
                        <a href="bookings/add.php" class="quick-action-btn btn btn-success">
                            <i class="fas fa-plus"></i><br>حجز جديد
                        </a>
                        <a href="bookings/list_optimized.php" class="quick-action-btn btn btn-primary">
                            <i class="fas fa-list"></i><br>قائمة الحجوزات
                        </a>
                        <a href="rooms/list.php" class="quick-action-btn btn btn-info">
                            <i class="fas fa-bed"></i><br>إدارة الغرف
                        </a>
                        <a href="reports/comprehensive_reports.php" class="quick-action-btn btn btn-warning">
                            <i class="fas fa-chart-bar"></i><br>التقارير
                        </a>
                        <a href="expenses/list.php" class="quick-action-btn btn btn-danger">
                            <i class="fas fa-money-bill"></i><br>المصروفات
                        </a>
                        <a href="cash/register.php" class="quick-action-btn btn btn-secondary">
                            <i class="fas fa-cash-register"></i><br>الصندوق
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Room Status -->
            <div class="col-lg-6">
                <div class="dashboard-card">
                    <h5><i class="fas fa-bed"></i> حالة الغرف</h5>
                    
                    <?php if (isset($room_status['محجوزة'])): ?>
                        <div class="mb-3">
                            <h6 class="text-warning">غرف محجوزة (<?php echo $room_status['محجوزة']['count']; ?>)</h6>
                            <div class="room-grid">
                                <?php 
                                $occupied_rooms = explode(',', $room_status['محجوزة']['rooms']);
                                foreach ($occupied_rooms as $room): 
                                ?>
                                    <div class="room-item room-occupied"><?php echo trim($room); ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($room_status['شاغرة'])): ?>
                        <div>
                            <h6 class="text-success">غرف شاغرة (<?php echo $room_status['شاغرة']['count']; ?>)</h6>
                            <div class="room-grid">
                                <?php 
                                $vacant_rooms = explode(',', $room_status['شاغرة']['rooms']);
                                foreach (array_slice($vacant_rooms, 0, 20) as $room): // Show first 20 rooms
                                ?>
                                    <div class="room-item room-vacant"><?php echo trim($room); ?></div>
                                <?php endforeach; ?>
                                <?php if (count($vacant_rooms) > 20): ?>
                                    <div class="room-item room-vacant">+<?php echo count($vacant_rooms) - 20; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="col-lg-6">
                <div class="dashboard-card">
                    <h5><i class="fas fa-clock"></i> النشاطات الأخيرة</h5>
                    
                    <?php if (empty($recent_activities)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2"></i>
                            <p>لا توجد نشاطات حديثة</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_activities as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon bg-<?php echo $activity['status']; ?> text-white">
                                    <?php
                                    $icons = [
                                        'booking' => 'fas fa-bed',
                                        'payment' => 'fas fa-money-bill',
                                        'expense' => 'fas fa-shopping-cart'
                                    ];
                                    ?>
                                    <i class="<?php echo $icons[$activity['type']] ?? 'fas fa-info'; ?>"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title"><?php echo htmlspecialchars($activity['title']); ?></div>
                                    <div class="activity-time"><?php echo $activity['time']; ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Financial Chart -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <h5><i class="fas fa-chart-line"></i> الملخص المالي</h5>
                    <div class="chart-container">
                        <canvas id="financialChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Badge -->
        <div class="performance-badge">
            <i class="fas fa-tachometer-alt"></i>
            تم التحميل في <?php echo number_format($load_time * 1000, 1); ?> مللي ثانية
        </div>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Financial Chart
        const ctx = document.getElementById('financialChart').getContext('2d');
        const financialChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['إيراد اليوم', 'مصروفات اليوم', 'إيراد الأسبوع', 'إيراد الشهر'],
                datasets: [{
                    label: 'المبلغ (ريال)',
                    data: [
                        <?php echo $stats['today_revenue'] ?? 0; ?>,
                        <?php echo $stats['today_expenses'] ?? 0; ?>,
                        <?php echo $financial_summary['weekly_revenue'] ?? 0; ?>,
                        <?php echo $financial_summary['monthly_revenue'] ?? 0; ?>
                    ],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(220, 53, 69, 0.8)',
                        'rgba(23, 162, 184, 0.8)',
                        'rgba(255, 193, 7, 0.8)'
                    ],
                    borderColor: [
                        'rgba(40, 167, 69, 1)',
                        'rgba(220, 53, 69, 1)',
                        'rgba(23, 162, 184, 1)',
                        'rgba(255, 193, 7, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString() + ' ريال';
                            }
                        }
                    }
                }
            }
        });

        // Auto-refresh every 5 minutes
        setTimeout(() => {
            location.reload();
        }, 300000);

        // Add loading animation
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.dashboard-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s, transform 0.5s';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>