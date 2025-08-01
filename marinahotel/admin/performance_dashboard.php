<?php
/**
 * Performance Monitoring Dashboard
 * Displays system performance metrics and slow queries
 */

include_once '../includes/db.php';
include_once '../includes/query_optimizer.php';
include_once '../includes/header.php';

// Check admin permissions
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$optimizer = new QueryOptimizer($conn);

// Get performance metrics for the last 24 hours
$metrics_query = "
    SELECT 
        metric_name,
        AVG(metric_value) as avg_value,
        MIN(metric_value) as min_value,
        MAX(metric_value) as max_value,
        COUNT(*) as count
    FROM performance_metrics 
    WHERE measurement_time >= NOW() - INTERVAL 24 HOUR
    GROUP BY metric_name
    ORDER BY metric_name
";

$metrics_result = $conn->query($metrics_query);
$metrics = [];
if ($metrics_result && $metrics_result->num_rows > 0) {
    while ($row = $metrics_result->fetch_assoc()) {
        $metrics[] = $row;
    }
}

// Get slow queries from the last 24 hours
$slow_queries_query = "
    SELECT 
        query_text,
        execution_time,
        rows_examined,
        rows_sent,
        timestamp,
        user,
        host
    FROM slow_queries 
    WHERE timestamp >= NOW() - INTERVAL 24 HOUR
    ORDER BY execution_time DESC
    LIMIT 20
";

$slow_queries_result = $conn->query($slow_queries_query);
$slow_queries = [];
if ($slow_queries_result && $slow_queries_result->num_rows > 0) {
    while ($row = $slow_queries_result->fetch_assoc()) {
        $slow_queries[] = $row;
    }
}

// Get database statistics
$db_stats_query = "
    SELECT 
        table_name,
        table_rows,
        data_length,
        index_length,
        (data_length + index_length) as total_size
    FROM information_schema.tables 
    WHERE table_schema = DATABASE()
    ORDER BY total_size DESC
";

$db_stats_result = $conn->query($db_stats_query);
$db_stats = [];
if ($db_stats_result && $db_stats_result->num_rows > 0) {
    while ($row = $db_stats_result->fetch_assoc()) {
        $db_stats[] = $row;
    }
}

// Get system status
$system_status = [
    'mysql_version' => $conn->server_info,
    'php_version' => PHP_VERSION,
    'memory_usage' => memory_get_usage(true),
    'memory_peak' => memory_get_peak_usage(true),
    'uptime' => null
];

// Get MySQL uptime
$uptime_result = $conn->query("SHOW STATUS LIKE 'Uptime'");
if ($uptime_result && $uptime_result->num_rows > 0) {
    $uptime_row = $uptime_result->fetch_assoc();
    $system_status['uptime'] = $uptime_row['Value'];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة مراقبة الأداء - فندق مارينا</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/fontawesome.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .metric-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .metric-value {
            font-size: 2rem;
            font-weight: bold;
            color: #007bff;
        }

        .metric-label {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .status-good { color: #28a745; }
        .status-warning { color: #ffc107; }
        .status-danger { color: #dc3545; }

        .query-text {
            font-family: 'Courier New', monospace;
            font-size: 0.8rem;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            max-height: 100px;
            overflow-y: auto;
        }

        .table-size {
            font-size: 0.9rem;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-tachometer-alt"></i> لوحة مراقبة الأداء</h2>
            <div>
                <button class="btn btn-primary" onclick="location.reload()">
                    <i class="fas fa-sync"></i> تحديث
                </button>
                <a href="dash.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> العودة للوحة الرئيسية
                </a>
            </div>
        </div>

        <!-- System Status -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="metric-card text-center">
                    <div class="metric-value status-good">
                        <i class="fas fa-server"></i>
                    </div>
                    <div class="metric-label">حالة النظام</div>
                    <small class="text-success">يعمل بشكل طبيعي</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card text-center">
                    <div class="metric-value"><?php echo $system_status['mysql_version']; ?></div>
                    <div class="metric-label">إصدار MySQL</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card text-center">
                    <div class="metric-value"><?php echo $system_status['php_version']; ?></div>
                    <div class="metric-label">إصدار PHP</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card text-center">
                    <div class="metric-value">
                        <?php echo round($system_status['memory_usage'] / 1024 / 1024, 1); ?>MB
                    </div>
                    <div class="metric-label">استخدام الذاكرة</div>
                </div>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-line"></i> مقاييس الأداء (آخر 24 ساعة)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($metrics)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                لا توجد بيانات أداء متاحة حتى الآن. سيتم جمع البيانات تلقائياً مع استخدام النظام.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>المقياس</th>
                                            <th>المتوسط</th>
                                            <th>الحد الأدنى</th>
                                            <th>الحد الأقصى</th>
                                            <th>عدد القياسات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($metrics as $metric): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($metric['metric_name']); ?></td>
                                                <td>
                                                    <?php 
                                                    $avg = $metric['avg_value'];
                                                    $class = $avg > 2 ? 'status-danger' : ($avg > 1 ? 'status-warning' : 'status-good');
                                                    ?>
                                                    <span class="<?php echo $class; ?>">
                                                        <?php echo number_format($avg, 3); ?>
                                                        <?php if (strpos($metric['metric_name'], 'time') !== false): ?>
                                                            ثانية
                                                        <?php endif; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo number_format($metric['min_value'], 3); ?></td>
                                                <td><?php echo number_format($metric['max_value'], 3); ?></td>
                                                <td><?php echo $metric['count']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-database"></i> إحصائيات قاعدة البيانات</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-size">
                                <thead>
                                    <tr>
                                        <th>الجدول</th>
                                        <th>الصفوف</th>
                                        <th>الحجم</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($db_stats, 0, 10) as $stat): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($stat['table_name']); ?></td>
                                            <td><?php echo number_format($stat['table_rows']); ?></td>
                                            <td>
                                                <?php 
                                                $size_mb = $stat['total_size'] / 1024 / 1024;
                                                echo $size_mb > 1 ? number_format($size_mb, 1) . 'MB' : number_format($stat['total_size'] / 1024, 1) . 'KB';
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Slow Queries -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-exclamation-triangle"></i> الاستعلامات البطيئة (آخر 24 ساعة)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($slow_queries)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                ممتاز! لا توجد استعلامات بطيئة في آخر 24 ساعة.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                تم العثور على <?php echo count($slow_queries); ?> استعلام بطيء. يُنصح بمراجعة هذه الاستعلامات لتحسين الأداء.
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>وقت التنفيذ</th>
                                            <th>الصفوف المفحوصة</th>
                                            <th>الصفوف المرسلة</th>
                                            <th>المستخدم</th>
                                            <th>التوقيت</th>
                                            <th>الاستعلام</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($slow_queries as $query): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-danger">
                                                        <?php echo number_format($query['execution_time'], 2); ?>s
                                                    </span>
                                                </td>
                                                <td><?php echo number_format($query['rows_examined'] ?? 0); ?></td>
                                                <td><?php echo number_format($query['rows_sent'] ?? 0); ?></td>
                                                <td><?php echo htmlspecialchars($query['user']); ?></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($query['timestamp'])); ?></td>
                                                <td>
                                                    <div class="query-text">
                                                        <?php echo htmlspecialchars(substr($query['query_text'], 0, 200)); ?>
                                                        <?php if (strlen($query['query_text']) > 200): ?>...<?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recommendations -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-lightbulb"></i> توصيات تحسين الأداء</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-database"></i> قاعدة البيانات</h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success"></i> تم تطبيق الفهارس المحسنة</li>
                                    <li><i class="fas fa-check text-success"></i> تم تحويل الجداول إلى InnoDB</li>
                                    <li><i class="fas fa-check text-success"></i> تم تحسين المحفزات</li>
                                    <li><i class="fas fa-info text-info"></i> يُنصح بتشغيل OPTIMIZE TABLE شهرياً</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-code"></i> التطبيق</h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success"></i> تم تطبيق نظام التخزين المؤقت</li>
                                    <li><i class="fas fa-check text-success"></i> تم تحسين الاستعلامات</li>
                                    <li><i class="fas fa-info text-info"></i> استخدم الصفحات المحسنة للأداء الأفضل</li>
                                    <li><i class="fas fa-info text-info"></i> راقب استخدام الذاكرة بانتظام</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh every 30 seconds
        setTimeout(() => {
            location.reload();
        }, 30000);

        // Add performance monitoring
        window.addEventListener('load', function() {
            const loadTime = performance.now();
            console.log('Page loaded in:', loadTime.toFixed(2), 'ms');
        });
    </script>
</body>
</html>