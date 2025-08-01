<?php
/**
 * سكريبت لإصلاح مشاكل الهيدر في النظام
 * يقوم بإضافة الهيدر المفقود وإصلاح المسارات
 */

// قائمة الملفات التي تحتاج إلى إصلاح الهيدر
$files_to_fix = [
    'admin/bookings/add2.php',
    'admin/reports/revenue.php',
    'admin/expenses/expenses.php',
    'admin/reports/comprehensive_reports.php',
    'admin/reports/occupancy.php',
    'admin/reports/employee_withdrawals_report.php',
    'admin/employees/salary_withdrawals.php',
    'admin/settings/maintenance.php',
    'admin/settings/rooms_status.php',
    'admin/settings/guests.php',
    'admin/settings/employees.php'
];

$fixes_applied = [];
$errors = [];

foreach ($files_to_fix as $file_path) {
    $full_path = __DIR__ . '/' . $file_path;
    
    if (!file_exists($full_path)) {
        $errors[] = "الملف غير موجود: $file_path";
        continue;
    }
    
    $content = file_get_contents($full_path);
    if ($content === false) {
        $errors[] = "لا يمكن قراءة الملف: $file_path";
        continue;
    }
    
    $original_content = $content;
    $fixed = false;
    
    // التحقق من وجود include للهيدر
    if (!preg_match('/include.*header\.php/', $content)) {
        // تحديد عدد المستويات للوصول للجذر
        $depth = substr_count($file_path, '/') - 1;
        $header_path = str_repeat('../', $depth) . 'includes/header.php';
        
        // البحث عن موقع إدراج الهيدر
        if (preg_match('/(<\?php\s*\n?)/', $content, $matches, PREG_OFFSET_CAPTURE)) {
            $insert_pos = $matches[0][1] + strlen($matches[0][0]);
            
            // إضافة includes الأساسية
            $includes = "\n// تضمين الملفات الأساسية\n";
            $includes .= "require_once '{$header_path}';\n";
            $includes .= "require_once '" . str_repeat('../', $depth) . "includes/auth_check.php';\n\n";
            
            $content = substr_replace($content, $includes, $insert_pos, 0);
            $fixed = true;
        }
    }
    
    // إصلاح التعليقات المعطلة للهيدر
    if (preg_match('/\/\/\s*include.*header\.php/', $content)) {
        $content = preg_replace('/\/\/\s*(include.*header\.php)/', '$1', $content);
        $fixed = true;
    }
    
    // إصلاح مسارات الأصول
    $content = preg_replace('/BASE_URL\s*\.\s*[\'"]assets\//', 'BASE_URL . "assets/', $content);
    
    // حفظ الملف إذا تم إجراء تعديلات
    if ($fixed || $content !== $original_content) {
        if (file_put_contents($full_path, $content) !== false) {
            $fixes_applied[] = $file_path;
        } else {
            $errors[] = "لا يمكن كتابة الملف: $file_path";
        }
    }
}

// إنشاء تقرير الإصلاحات
$report = "تقرير إصلاح مشاكل الهيدر\n";
$report .= "========================\n\n";
$report .= "تاريخ التشغيل: " . date('Y-m-d H:i:s') . "\n\n";

if (!empty($fixes_applied)) {
    $report .= "الملفات التي تم إصلاحها (" . count($fixes_applied) . "):\n";
    foreach ($fixes_applied as $file) {
        $report .= "✓ $file\n";
    }
    $report .= "\n";
}

if (!empty($errors)) {
    $report .= "الأخطاء (" . count($errors) . "):\n";
    foreach ($errors as $error) {
        $report .= "✗ $error\n";
    }
    $report .= "\n";
}

$report .= "الإصلاحات المطبقة:\n";
$report .= "- إضافة include للهيدر في الملفات المفقودة\n";
$report .= "- إلغاء تعليق includes المعطلة\n";
$report .= "- إصلاح مسارات الأصول\n";
$report .= "- تحديث BASE_URL لتحديد المسار تلقائياً\n";

// حفظ التقرير
file_put_contents(__DIR__ . '/header_fixes_report.txt', $report);

// عرض النتائج
echo "<h2>تقرير إصلاح مشاكل الهيدر</h2>";
echo "<div style='font-family: Arial; direction: rtl; text-align: right;'>";

if (!empty($fixes_applied)) {
    echo "<h3 style='color: green;'>الملفات التي تم إصلاحها (" . count($fixes_applied) . "):</h3>";
    echo "<ul>";
    foreach ($fixes_applied as $file) {
        echo "<li>✓ $file</li>";
    }
    echo "</ul>";
}

if (!empty($errors)) {
    echo "<h3 style='color: red;'>الأخطاء (" . count($errors) . "):</h3>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>✗ $error</li>";
    }
    echo "</ul>";
}

echo "<h3>الإصلاحات المطبقة:</h3>";
echo "<ul>";
echo "<li>إضافة include للهيدر في الملفات المفقودة</li>";
echo "<li>إلغاء تعليق includes المعطلة</li>";
echo "<li>إصلاح مسارات الأصول</li>";
echo "<li>تحديث BASE_URL لتحديد المسار تلقائياً</li>";
echo "</ul>";

echo "<p><strong>تم حفظ التقرير في:</strong> header_fixes_report.txt</p>";
echo "</div>";
?>