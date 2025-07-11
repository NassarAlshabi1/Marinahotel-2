<?php
/**
 * تشغيل جميع الإصلاحات معاً
 * Run All Fixes Together
 */

echo "🚀 بدء تشغيل جميع الإصلاحات...\n\n";

// قائمة الإصلاحات بالترتيب
$fixes = [
    'quick_fix.php' => 'الإصلاح السريع',
    'fix_security_issues.php' => 'إصلاح مشاكل الأمان',
    'fix_function_conflicts.php' => 'إصلاح تضارب الدوال'
];

$success_count = 0;
$total_fixes = count($fixes);

foreach ($fixes as $file => $description) {
    echo "🔧 تشغيل: {$description}\n";
    echo "📂 الملف: {$file}\n";
    
    if (file_exists($file)) {
        ob_start();
        try {
            include $file;
            $output = ob_get_clean();
            echo "✅ تم بنجاح\n";
            $success_count++;
            
            // عرض آخر 3 أسطر من النتيجة
            $lines = explode("\n", trim($output));
            $last_lines = array_slice($lines, -3);
            foreach ($last_lines as $line) {
                if (!empty(trim($line))) {
                    echo "   💡 " . trim($line) . "\n";
                }
            }
            
        } catch (Exception $e) {
            ob_end_clean();
            echo "❌ فشل: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ الملف غير موجود\n";
    }
    
    echo str_repeat("-", 50) . "\n";
}

echo "\n=== النتيجة النهائية ===\n";
echo "إجمالي الإصلاحات: {$total_fixes}\n";
echo "نجح: {$success_count}\n";
echo "فشل: " . ($total_fixes - $success_count) . "\n";

if ($success_count === $total_fixes) {
    echo "\n🎉 جميع الإصلاحات تمت بنجاح!\n";
    echo "✅ النظام جاهز للاستخدام\n\n";
    
    echo "🧪 للاختبار:\n";
    echo "1. php test_functions.php\n";
    echo "2. http://localhost/marinahotel/test_functions.php\n";
    echo "3. http://localhost/marinahotel/login.php (admin/1234)\n\n";
    
} else {
    echo "\n⚠️  بعض الإصلاحات فشلت\n";
    echo "📋 راجع التفاصيل أعلاه لمعرفة المشاكل\n\n";
}

echo "📚 للمزيد من المعلومات، راجع:\n";
echo "- FINAL_FIXES.md\n";
echo "- SECURITY_FIXES_README.md\n\n";

echo "🛠️ ملفات الإصلاح المتوفرة:\n";
foreach ($fixes as $file => $desc) {
    $status = file_exists($file) ? "✅" : "❌";
    echo "{$status} {$file} - {$desc}\n";
}

echo "\n🏁 انتهى تشغيل جميع الإصلاحات\n";
?>