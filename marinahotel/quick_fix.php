<?php
/**
 * إصلاح سريع للمشاكل الأساسية
 * Quick Fix for Basic Issues
 */

echo "🚀 بدء الإصلاح السريع...\n\n";

// إصلاح 1: إنشاء مجلد logs
if (!is_dir('logs')) {
    mkdir('logs', 0755, true);
    echo "✅ تم إنشاء مجلد logs\n";
}

// إصلاح 2: إنشاء ملف .htaccess لحماية logs
file_put_contents('logs/.htaccess', "Order Deny,Allow\nDeny from all\n");
echo "✅ تم حماية مجلد logs\n";

// إصلاح 3: تحديث إعدادات الجلسة في login.php
$login_content = file_get_contents('login.php');
if (strpos($login_content, 'session_status() === PHP_SESSION_NONE') === false) {
    $old_session_code = 'ini_set(\'session.cookie_httponly\', 1);
ini_set(\'session.cookie_secure\', 1);
ini_set(\'session.use_strict_mode\', 1);
session_start();';
    
    $new_session_code = 'if (session_status() === PHP_SESSION_NONE) {
    ini_set(\'session.cookie_httponly\', 1);
    ini_set(\'session.cookie_secure\', 0);
    ini_set(\'session.use_strict_mode\', 1);
    session_start();
}';
    
    $login_content = str_replace($old_session_code, $new_session_code, $login_content);
    file_put_contents('login.php', $login_content);
    echo "✅ تم إصلاح مشاكل الجلسة في login.php\n";
}

// إصلاح 4: إنشاء ملف تجريبي للاختبار
$test_content = '<?php
echo "<h2>🧪 اختبار سريع للنظام</h2>";
session_start();
echo "<p>✅ الجلسة تعمل</p>";
if (file_exists("includes/config.php")) {
    require_once "includes/config.php";
    echo "<p>✅ ملف الإعدادات موجود</p>";
}
echo "<p><a href=\"login.php\">تسجيل الدخول</a></p>";
?>';

file_put_contents('quick_test.php', $test_content);
echo "✅ تم إنشاء ملف الاختبار السريع\n";

echo "\n🎉 تم الانتهاء من الإصلاح السريع!\n";
echo "📋 التالي:\n";
echo "1. اختبر النظام: php quick_test.php\n";
echo "2. أو افتح: http://localhost/marinahotel/quick_test.php\n";
echo "3. للإصلاح الشامل: php fix_security_issues.php\n\n";
?>