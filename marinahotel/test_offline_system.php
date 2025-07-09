<?php
// ملف اختبار النظام المحدث للعمل بدون إنترنت
require_once 'includes/db.php';
require_once 'includes/functions.php';

echo "<!DOCTYPE html>";
echo "<html lang='ar' dir='rtl'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>اختبار النظام المحدث</title>";
echo "<link href='assets/css/bootstrap-complete.css' rel='stylesheet'>";
echo "<link href='assets/fonts/fonts.css' rel='stylesheet'>";
echo "<link href='assets/css/fontawesome.min.css' rel='stylesheet'>";
echo "<style>";
echo "body { font-family: 'Tajawal', sans-serif; direction: rtl; text-align: right; padding: 20px; }";
echo ".test-item { margin: 15px 0; padding: 15px; border-radius: 8px; }";
echo ".success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }";
echo ".error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }";
echo ".info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<h1 class='text-center mb-4'><i class='fas fa-cogs'></i> اختبار النظام المحدث</h1>";

$tests = [];

// اختبار 1: قاعدة البيانات
try {
    $conn->query("SELECT 1");
    $tests[] = ['name' => 'اتصال قاعدة البيانات', 'status' => 'success', 'message' => 'متصل بنجاح'];
} catch (Exception $e) {
    $tests[] = ['name' => 'اتصال قاعدة البيانات', 'status' => 'error', 'message' => 'فشل: ' . $e->getMessage()];
}

// اختبار 2: الجداول المطلوبة
$required_tables = ['whatsapp_messages', 'system_notifications'];
foreach ($required_tables as $table) {
    try {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            $tests[] = ['name' => "جدول $table", 'status' => 'success', 'message' => 'موجود'];
        } else {
            $tests[] = ['name' => "جدول $table", 'status' => 'info', 'message' => 'سيتم إنشاؤه تلقائياً'];
        }
    } catch (Exception $e) {
        $tests[] = ['name' => "جدول $table", 'status' => 'error', 'message' => 'خطأ: ' . $e->getMessage()];
    }
}

// اختبار 3: الملفات المحلية
$required_files = [
    'assets/css/bootstrap-complete.css' => 'Bootstrap CSS',
    'assets/js/bootstrap-full.js' => 'Bootstrap JS',
    'assets/js/sweetalert2.min.js' => 'SweetAlert2',
    'assets/js/jquery.min.js' => 'jQuery',
    'assets/fonts/fonts.css' => 'ملف الخطوط',
    'assets/css/fontawesome.min.css' => 'FontAwesome CSS'
];

foreach ($required_files as $file => $name) {
    if (file_exists($file)) {
        $size = round(filesize($file) / 1024, 2);
        $tests[] = ['name' => $name, 'status' => 'success', 'message' => "موجود ({$size} KB)"];
    } else {
        $tests[] = ['name' => $name, 'status' => 'error', 'message' => 'غير موجود'];
    }
}

// اختبار 4: المجلدات المطلوبة
$required_dirs = ['logs', 'api', 'assets/css', 'assets/js', 'assets/fonts'];
foreach ($required_dirs as $dir) {
    if (is_dir($dir)) {
        $writable = is_writable($dir) ? 'قابل للكتابة' : 'غير قابل للكتابة';
        $tests[] = ['name' => "مجلد $dir", 'status' => 'success', 'message' => "موجود ($writable)"];
    } else {
        $tests[] = ['name' => "مجلد $dir", 'status' => 'error', 'message' => 'غير موجود'];
    }
}

// اختبار 5: وظائف النظام
try {
    // اختبار وظيفة تنسيق الهاتف
    $phone_test = format_yemeni_phone('0771234567');
    if ($phone_test) {
        $tests[] = ['name' => 'تنسيق رقم الهاتف', 'status' => 'success', 'message' => "يعمل بشكل صحيح ($phone_test)"];
    } else {
        $tests[] = ['name' => 'تنسيق رقم الهاتف', 'status' => 'error', 'message' => 'لا يعمل'];
    }
} catch (Exception $e) {
    $tests[] = ['name' => 'تنسيق رقم الهاتف', 'status' => 'error', 'message' => 'خطأ: ' . $e->getMessage()];
}

// اختبار 6: إنشاء إشعار تجريبي
try {
    $notification_result = create_system_notification(
        'اختبار النظام',
        'تم اختبار النظام المحدث بنجاح',
        'info'
    );
    $tests[] = ['name' => 'إنشاء الإشعارات', 'status' => $notification_result ? 'success' : 'error', 'message' => $notification_result ? 'يعمل بشكل صحيح' : 'لا يعمل'];
} catch (Exception $e) {
    $tests[] = ['name' => 'إنشاء الإشعارات', 'status' => 'error', 'message' => 'خطأ: ' . $e->getMessage()];
}

// اختبار 7: حفظ رسالة واتساب تجريبية
try {
    $whatsapp_result = send_yemeni_whatsapp('967771234567', 'رسالة اختبار النظام المحدث');
    $status = $whatsapp_result['status'] ?? 'error';
    $message = $whatsapp_result['message'] ?? 'خطأ غير معروف';
    $tests[] = ['name' => 'نظام الواتساب', 'status' => $status === 'error' ? 'error' : 'success', 'message' => $message];
} catch (Exception $e) {
    $tests[] = ['name' => 'نظام الواتساب', 'status' => 'error', 'message' => 'خطأ: ' . $e->getMessage()];
}

// عرض النتائج
$success_count = 0;
$error_count = 0;

foreach ($tests as $test) {
    $class = $test['status'];
    if ($test['status'] === 'success') $success_count++;
    if ($test['status'] === 'error') $error_count++;
    
    echo "<div class='test-item $class'>";
    echo "<strong><i class='fas fa-" . ($test['status'] === 'success' ? 'check' : ($test['status'] === 'error' ? 'times' : 'info')) . "'></i> {$test['name']}</strong><br>";
    echo $test['message'];
    echo "</div>";
}

// ملخص النتائج
echo "<div class='card mt-4'>";
echo "<div class='card-header bg-primary text-white'>";
echo "<h3><i class='fas fa-chart-pie'></i> ملخص الاختبار</h3>";
echo "</div>";
echo "<div class='card-body'>";
echo "<div class='row'>";
echo "<div class='col-md-4'>";
echo "<div class='card bg-success text-white'>";
echo "<div class='card-body text-center'>";
echo "<h4>$success_count</h4>";
echo "<p>اختبار نجح</p>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "<div class='col-md-4'>";
echo "<div class='card bg-danger text-white'>";
echo "<div class='card-body text-center'>";
echo "<h4>$error_count</h4>";
echo "<p>اختبار فشل</p>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "<div class='col-md-4'>";
echo "<div class='card bg-info text-white'>";
echo "<div class='card-body text-center'>";
echo "<h4>" . count($tests) . "</h4>";
echo "<p>إجمالي الاختبارات</p>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

if ($error_count === 0) {
    echo "<div class='alert alert-success mt-3'>";
    echo "<h4><i class='fas fa-check-circle'></i> النظام جاهز للاستخدام!</h4>";
    echo "<p>جميع الاختبارات نجحت. يمكنك الآن استخدام النظام بدون إنترنت.</p>";
    echo "<a href='admin/dash.php' class='btn btn-success'><i class='fas fa-arrow-right'></i> انتقل إلى لوحة التحكم</a>";
    echo "</div>";
} else {
    echo "<div class='alert alert-warning mt-3'>";
    echo "<h4><i class='fas fa-exclamation-triangle'></i> يوجد مشاكل تحتاج إصلاح</h4>";
    echo "<p>يرجى مراجعة الأخطاء أعلاه وإصلاحها قبل استخدام النظام.</p>";
    echo "</div>";
}

echo "</div>";
echo "</div>";

// معلومات إضافية
echo "<div class='card mt-4'>";
echo "<div class='card-header bg-secondary text-white'>";
echo "<h4><i class='fas fa-info-circle'></i> معلومات إضافية</h4>";
echo "</div>";
echo "<div class='card-body'>";
echo "<ul>";
echo "<li><strong>إصدار PHP:</strong> " . PHP_VERSION . "</li>";
echo "<li><strong>الخادم:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</li>";
echo "<li><strong>الوقت الحالي:</strong> " . date('Y-m-d H:i:s') . "</li>";
echo "<li><strong>المنطقة الزمنية:</strong> " . date_default_timezone_get() . "</li>";
if (function_exists('curl_version')) {
    $curl_info = curl_version();
    echo "<li><strong>إصدار cURL:</strong> " . $curl_info['version'] . "</li>";
}
echo "<li><strong>دعم الواتساب:</strong> " . (is_internet_available() ? 'متصل بالإنترنت' : 'غير متصل - سيتم حفظ الرسائل محلياً') . "</li>";
echo "</ul>";
echo "</div>";
echo "</div>";

echo "</div>";

echo "<script src='assets/js/jquery.min.js'></script>";
echo "<script src='assets/js/bootstrap-full.js'></script>";
echo "<script>";
echo "console.log('اختبار النظام المحدث - تم التحميل بنجاح');";
echo "if (typeof $ !== 'undefined') console.log('jQuery محمل محلياً');";
echo "if (typeof bootstrap !== 'undefined') console.log('Bootstrap محمل محلياً');";
echo "</script>";

echo "</body>";
echo "</html>";
?>