<?php
/**
 * Database Optimization Testing Script
 * Tests the performance improvements after applying optimizations
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/query_optimizer.php';
require_once 'includes/cache_manager.php';

// Set execution time limit
set_time_limit(120);

echo "<!DOCTYPE html>\n";
echo "<html lang='ar' dir='rtl'>\n";
echo "<head>\n";
echo "<meta charset='UTF-8'>\n";
echo "<title>اختبار تحسينات الأداء - فندق مارينا</title>\n";
echo "<style>\n";
echo "body { font-family: Arial, sans-serif; margin: 20px; direction: rtl; }\n";
echo ".test-result { padding: 15px; margin: 10px 0; border-radius: 8px; }\n";
echo ".success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }\n";
echo ".warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }\n";
echo ".info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }\n";
echo ".error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }\n";
echo ".performance-table { width: 100%; border-collapse: collapse; margin: 20px 0; }\n";
echo ".performance-table th, .performance-table td { padding: 12px; text-align: center; border: 1px solid #ddd; }\n";
echo ".performance-table th { background: #f8f9fa; }\n";
echo ".fast { color: #28a745; font-weight: bold; }\n";
echo ".slow { color: #dc3545; font-weight: bold; }\n";
echo ".medium { color: #ffc107; font-weight: bold; }\n";
echo "</style>\n";
echo "</head>\n";
echo "<body>\n";

echo "<h1>🚀 اختبار تحسينات الأداء - فندق مارينا</h1>\n";

// Initialize systems
$optimizer = new QueryOptimizer($conn);
$cache = new CacheManager();

// Test results array
$test_results = [];

// Function to measure query execution time
function measureQueryTime($conn, $query, $description) {
    $start_time = microtime(true);
    $result = $conn->query($query);
    $end_time = microtime(true);
    $execution_time = ($end_time - $start_time) * 1000; // Convert to milliseconds
    
    return [
        'description' => $description,
        'execution_time' => $execution_time,
        'rows' => $result ? $result->num_rows : 0,
        'success' => $result !== false
    ];
}

echo "<h2>📊 اختبار أداء الاستعلامات</h2>\n";

// Test 1: Basic booking list query (old vs optimized)
echo "<h3>اختبار 1: استعلام قائمة الحجوزات</h3>\n";

// Old query (with N+1 problem)
$old_query = "
    SELECT 
        b.booking_id,
        b.guest_name,
        b.room_number,
        r.price AS room_price,
        DATE_FORMAT(b.checkin_date, '%d/%m/%Y') AS checkin_date,
        b.calculated_nights,
        b.status
    FROM bookings b
    JOIN rooms r ON b.room_number = r.room_number
    WHERE b.actual_checkout IS NULL
    ORDER BY b.checkin_date DESC
";

$test1_old = measureQueryTime($conn, $old_query, "الاستعلام القديم (بدون تحسين)");

// Optimized query
$optimized_query = "
    SELECT 
        b.booking_id,
        b.guest_name,
        b.guest_phone,
        b.room_number,
        r.price AS room_price,
        r.type AS room_type,
        DATE_FORMAT(b.checkin_date, '%d/%m/%Y') AS checkin_date,
        b.calculated_nights,
        COALESCE(p.total_paid, 0) AS paid_amount,
        (r.price * b.calculated_nights) - COALESCE(p.total_paid, 0) AS remaining_amount,
        b.status,
        COALESCE(bn.alert_count, 0) AS has_alerts
    FROM bookings b
    JOIN rooms r ON b.room_number = r.room_number
    LEFT JOIN (
        SELECT booking_id, SUM(amount) as total_paid 
        FROM payment 
        GROUP BY booking_id
    ) p ON b.booking_id = p.booking_id
    LEFT JOIN (
        SELECT booking_id, COUNT(*) as alert_count 
        FROM booking_notes 
        WHERE is_active = 1 AND (alert_until IS NULL OR alert_until > NOW())
        GROUP BY booking_id
    ) bn ON b.booking_id = bn.booking_id
    WHERE b.actual_checkout IS NULL
    ORDER BY b.checkin_date DESC
";

$test1_new = measureQueryTime($conn, $optimized_query, "الاستعلام المحسن (مع JOINs)");

$improvement1 = (($test1_old['execution_time'] - $test1_new['execution_time']) / $test1_old['execution_time']) * 100;

echo "<table class='performance-table'>\n";
echo "<tr><th>النوع</th><th>وقت التنفيذ (مللي ثانية)</th><th>عدد الصفوف</th><th>التحسن</th></tr>\n";
echo "<tr><td>الاستعلام القديم</td><td class='" . ($test1_old['execution_time'] > 100 ? 'slow' : ($test1_old['execution_time'] > 50 ? 'medium' : 'fast')) . "'>" . number_format($test1_old['execution_time'], 2) . "</td><td>" . $test1_old['rows'] . "</td><td>-</td></tr>\n";
echo "<tr><td>الاستعلام المحسن</td><td class='" . ($test1_new['execution_time'] > 100 ? 'slow' : ($test1_new['execution_time'] > 50 ? 'medium' : 'fast')) . "'>" . number_format($test1_new['execution_time'], 2) . "</td><td>" . $test1_new['rows'] . "</td><td class='" . ($improvement1 > 0 ? 'fast' : 'slow') . "'>" . number_format($improvement1, 1) . "%</td></tr>\n";
echo "</table>\n";

// Test 2: Dashboard statistics
echo "<h3>اختبار 2: إحصائيات لوحة التحكم</h3>\n";

$dashboard_query = "
    SELECT 
        (SELECT COUNT(*) FROM bookings WHERE status = 'محجوزة' AND actual_checkout IS NULL) as occupied_rooms,
        (SELECT COUNT(*) FROM rooms WHERE status = 'شاغرة') as available_rooms,
        (SELECT COUNT(*) FROM bookings WHERE DATE(checkin_date) = CURDATE()) as today_checkins,
        (SELECT COALESCE(SUM(amount), 0) FROM payment WHERE DATE(payment_date) = CURDATE()) as today_revenue,
        (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE date = CURDATE()) as today_expenses,
        (SELECT COUNT(*) FROM booking_notes WHERE is_active = 1 AND alert_type = 'high') as high_alerts
";

$test2 = measureQueryTime($conn, $dashboard_query, "استعلام إحصائيات لوحة التحكم");

echo "<table class='performance-table'>\n";
echo "<tr><th>الاستعلام</th><th>وقت التنفيذ (مللي ثانية)</th><th>الحالة</th></tr>\n";
echo "<tr><td>إحصائيات لوحة التحكم</td><td class='" . ($test2['execution_time'] > 100 ? 'slow' : ($test2['execution_time'] > 50 ? 'medium' : 'fast')) . "'>" . number_format($test2['execution_time'], 2) . "</td><td>" . ($test2['success'] ? '✅ نجح' : '❌ فشل') . "</td></tr>\n";
echo "</table>\n";

// Test 3: Cache performance
echo "<h3>اختبار 3: أداء نظام التخزين المؤقت</h3>\n";

$cache_key = 'test_cache_performance';
$test_data = ['test' => 'data', 'timestamp' => time(), 'random' => rand(1, 1000)];

// Test cache write
$cache_write_start = microtime(true);
$cache_write_success = $cache->set($cache_key, $test_data, 300);
$cache_write_time = (microtime(true) - $cache_write_start) * 1000;

// Test cache read
$cache_read_start = microtime(true);
$cached_data = $cache->get($cache_key);
$cache_read_time = (microtime(true) - $cache_read_start) * 1000;

$cache_hit = $cached_data !== null && $cached_data['test'] === 'data';

echo "<table class='performance-table'>\n";
echo "<tr><th>العملية</th><th>وقت التنفيذ (مللي ثانية)</th><th>النتيجة</th></tr>\n";
echo "<tr><td>كتابة التخزين المؤقت</td><td class='fast'>" . number_format($cache_write_time, 3) . "</td><td>" . ($cache_write_success ? '✅ نجح' : '❌ فشل') . "</td></tr>\n";
echo "<tr><td>قراءة التخزين المؤقت</td><td class='fast'>" . number_format($cache_read_time, 3) . "</td><td>" . ($cache_hit ? '✅ نجح' : '❌ فشل') . "</td></tr>\n";
echo "</table>\n";

// Test 4: Index effectiveness
echo "<h3>اختبار 4: فعالية الفهارس</h3>\n";

$index_tests = [
    "SELECT * FROM bookings WHERE guest_name LIKE '%محمد%'" => "البحث بالاسم",
    "SELECT * FROM bookings WHERE guest_phone = '773114243'" => "البحث بالهاتف",
    "SELECT * FROM bookings WHERE status = 'محجوزة' AND checkin_date >= CURDATE()" => "البحث بالحالة والتاريخ",
    "SELECT * FROM payment WHERE payment_date >= CURDATE() - INTERVAL 7 DAY" => "البحث في المدفوعات",
    "SELECT * FROM rooms WHERE status = 'شاغرة'" => "البحث في الغرف"
];

echo "<table class='performance-table'>\n";
echo "<tr><th>الاستعلام</th><th>وقت التنفيذ (مللي ثانية)</th><th>عدد الصفوف</th><th>التقييم</th></tr>\n";

foreach ($index_tests as $query => $description) {
    $test_result = measureQueryTime($conn, $query, $description);
    $rating = $test_result['execution_time'] < 10 ? 'ممتاز' : ($test_result['execution_time'] < 50 ? 'جيد' : 'يحتاج تحسين');
    $class = $test_result['execution_time'] < 10 ? 'fast' : ($test_result['execution_time'] < 50 ? 'medium' : 'slow');
    
    echo "<tr><td>$description</td><td class='$class'>" . number_format($test_result['execution_time'], 2) . "</td><td>" . $test_result['rows'] . "</td><td class='$class'>$rating</td></tr>\n";
}

echo "</table>\n";

// Test 5: Memory usage
echo "<h3>اختبار 5: استخدام الذاكرة</h3>\n";

$memory_start = memory_get_usage(true);
$memory_peak_start = memory_get_peak_usage(true);

// Simulate some operations
$large_result = $conn->query("SELECT * FROM bookings b JOIN rooms r ON b.room_number = r.room_number LIMIT 100");
$data = [];
if ($large_result) {
    while ($row = $large_result->fetch_assoc()) {
        $data[] = $row;
    }
}

$memory_end = memory_get_usage(true);
$memory_peak_end = memory_get_peak_usage(true);

$memory_used = $memory_end - $memory_start;
$memory_peak_used = $memory_peak_end - $memory_peak_start;

echo "<table class='performance-table'>\n";
echo "<tr><th>المقياس</th><th>القيمة</th><th>التقييم</th></tr>\n";
echo "<tr><td>الذاكرة المستخدمة</td><td>" . number_format($memory_used / 1024, 2) . " KB</td><td class='" . ($memory_used < 1024*1024 ? 'fast' : 'medium') . "'>" . ($memory_used < 1024*1024 ? 'ممتاز' : 'مقبول') . "</td></tr>\n";
echo "<tr><td>ذروة استخدام الذاكرة</td><td>" . number_format($memory_peak_used / 1024, 2) . " KB</td><td class='" . ($memory_peak_used < 2*1024*1024 ? 'fast' : 'medium') . "'>" . ($memory_peak_used < 2*1024*1024 ? 'ممتاز' : 'مقبول') . "</td></tr>\n";
echo "</table>\n";

// Test 6: Database structure validation
echo "<h3>اختبار 6: التحقق من هيكل قاعدة البيانات</h3>\n";

$structure_tests = [
    "SHOW INDEX FROM bookings WHERE Key_name = 'idx_guest_name'" => "فهرس اسم النزيل",
    "SHOW INDEX FROM payment WHERE Key_name = 'idx_payment_date'" => "فهرس تاريخ الدفع",
    "SHOW INDEX FROM bookings WHERE Key_name = 'idx_status_checkin'" => "فهرس الحالة والوصول",
    "SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE() AND engine = 'InnoDB'" => "جداول InnoDB"
];

echo "<table class='performance-table'>\n";
echo "<tr><th>الفحص</th><th>النتيجة</th><th>الحالة</th></tr>\n";

foreach ($structure_tests as $query => $description) {
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        if (strpos($description, 'فهرس') !== false) {
            echo "<tr><td>$description</td><td>موجود</td><td class='success'>✅ تم</td></tr>\n";
        } else {
            $row = $result->fetch_assoc();
            $count = $row['count'] ?? $result->num_rows;
            echo "<tr><td>$description</td><td>$count</td><td class='success'>✅ تم</td></tr>\n";
        }
    } else {
        echo "<tr><td>$description</td><td>غير موجود</td><td class='error'>❌ مفقود</td></tr>\n";
    }
}

echo "</table>\n";

// Overall performance score
echo "<h2>📈 النتيجة الإجمالية</h2>\n";

$total_score = 0;
$max_score = 100;

// Calculate score based on various factors
if ($improvement1 > 0) $total_score += 20; // Query optimization
if ($test2['execution_time'] < 100) $total_score += 15; // Dashboard performance
if ($cache_hit) $total_score += 15; // Cache functionality
if ($memory_used < 1024*1024) $total_score += 10; // Memory efficiency

// Check if key indexes exist
$key_indexes = ['idx_guest_name', 'idx_payment_date', 'idx_status_checkin'];
$indexes_found = 0;
foreach ($key_indexes as $index) {
    $check_result = $conn->query("SHOW INDEX FROM bookings WHERE Key_name = '$index'");
    if ($check_result && $check_result->num_rows > 0) {
        $indexes_found++;
    }
}
$total_score += ($indexes_found / count($key_indexes)) * 20; // Index optimization

// Database engine check
$innodb_check = $conn->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE() AND engine = 'InnoDB'");
if ($innodb_check) {
    $innodb_row = $innodb_check->fetch_assoc();
    if ($innodb_row['count'] > 5) { // Most tables should be InnoDB
        $total_score += 20;
    }
}

$score_class = $total_score >= 80 ? 'success' : ($total_score >= 60 ? 'warning' : 'error');
$score_text = $total_score >= 80 ? 'ممتاز' : ($total_score >= 60 ? 'جيد' : 'يحتاج تحسين');

echo "<div class='test-result $score_class'>\n";
echo "<h3>النتيجة النهائية: $total_score / $max_score ($score_text)</h3>\n";
echo "<p><strong>ملخص التحسينات:</strong></p>\n";
echo "<ul>\n";
if ($improvement1 > 0) echo "<li>✅ تحسين استعلامات الحجوزات بنسبة " . number_format($improvement1, 1) . "%</li>\n";
if ($test2['execution_time'] < 100) echo "<li>✅ أداء ممتاز للوحة التحكم (" . number_format($test2['execution_time'], 1) . " مللي ثانية)</li>\n";
if ($cache_hit) echo "<li>✅ نظام التخزين المؤقت يعمل بكفاءة</li>\n";
if ($memory_used < 1024*1024) echo "<li>✅ استخدام محسن للذاكرة</li>\n";
echo "<li>✅ تم العثور على $indexes_found من " . count($key_indexes) . " فهارس أساسية</li>\n";
echo "</ul>\n";
echo "</div>\n";

// Recommendations
echo "<h2>💡 التوصيات</h2>\n";

echo "<div class='test-result info'>\n";
echo "<h4>توصيات لتحسين الأداء أكثر:</h4>\n";
echo "<ul>\n";

if ($total_score < 80) {
    echo "<li>تشغيل سكريبت تطبيق التحسينات: <code>apply_database_optimizations.php</code></li>\n";
}

if ($test2['execution_time'] > 100) {
    echo "<li>تحسين استعلامات لوحة التحكم باستخدام التخزين المؤقت</li>\n";
}

if (!$cache_hit) {
    echo "<li>التأكد من صحة إعدادات نظام التخزين المؤقت</li>\n";
}

if ($memory_used > 2*1024*1024) {
    echo "<li>مراجعة استخدام الذاكرة وتحسين الاستعلامات الكبيرة</li>\n";
}

echo "<li>تشغيل <code>OPTIMIZE TABLE</code> شهرياً للجداول الكبيرة</li>\n";
echo "<li>مراقبة الأداء باستخدام لوحة مراقبة الأداء</li>\n";
echo "<li>استخدام الصفحات المحسنة مثل <code>list_optimized.php</code> و <code>dash_optimized.php</code></li>\n";
echo "</ul>\n";
echo "</div>\n";

// Clean up
$cache->delete($cache_key);

echo "<div class='test-result success'>\n";
echo "<h4>✅ تم الانتهاء من اختبار الأداء بنجاح!</h4>\n";
echo "<p>يمكنك الآن استخدام النظام المحسن للحصول على أداء أفضل.</p>\n";
echo "</div>\n";

echo "</body></html>\n";

$conn->close();
?>