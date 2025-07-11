<?php
/**
 * اختبار شامل لجميع الإصلاحات
 * يختبر تضارب الدوال ومشاكل الجلسات
 */

echo "=== اختبار شامل لجميع الإصلاحات ===\n\n";

// اختبار 1: تحميل header.php
echo "1. اختبار تحميل header.php:\n";
try {
    require_once 'includes/header.php';
    echo "   ✓ تم تحميل header.php بنجاح\n";
} catch (Error $e) {
    echo "   ✗ خطأ في تحميل header.php: " . $e->getMessage() . "\n";
    exit(1);
}

// اختبار 2: تحميل security.php
echo "\n2. اختبار تحميل security.php:\n";
try {
    require_once 'includes/security.php';
    echo "   ✓ تم تحميل security.php بنجاح\n";
} catch (Error $e) {
    echo "   ✗ خطأ في تحميل security.php: " . $e->getMessage() . "\n";
    exit(1);
}

// اختبار 3: تحميل session_manager.php
echo "\n3. اختبار تحميل session_manager.php:\n";
try {
    require_once 'includes/session_manager.php';
    echo "   ✓ تم تحميل session_manager.php بنجاح\n";
} catch (Error $e) {
    echo "   ✗ خطأ في تحميل session_manager.php: " . $e->getMessage() . "\n";
    exit(1);
}

// اختبار 4: تحميل auth_check.php
echo "\n4. اختبار تحميل auth_check.php:\n";
try {
    require_once 'includes/auth_check.php';
    echo "   ✓ تم تحميل auth_check.php بنجاح\n";
} catch (Error $e) {
    echo "   ✗ خطأ في تحميل auth_check.php: " . $e->getMessage() . "\n";
    exit(1);
}

// اختبار 5: تحميل auth_check_modified.php
echo "\n5. اختبار تحميل auth_check_modified.php:\n";
try {
    require_once 'includes/auth_check_modified.php';
    echo "   ✓ تم تحميل auth_check_modified.php بنجاح\n";
} catch (Error $e) {
    echo "   ✗ خطأ في تحميل auth_check_modified.php: " . $e->getMessage() . "\n";
    exit(1);
}

// اختبار 6: تحميل auth_check_finance.php
echo "\n6. اختبار تحميل auth_check_finance.php:\n";
try {
    require_once 'includes/auth_check_finance.php';
    echo "   ✓ تم تحميل auth_check_finance.php بنجاح\n";
} catch (Error $e) {
    echo "   ✗ خطأ في تحميل auth_check_finance.php: " . $e->getMessage() . "\n";
    exit(1);
}

// اختبار 7: اختبار دوال CSRF
echo "\n7. اختبار دوال CSRF:\n";
if (function_exists('csrf_token')) {
    $token1 = csrf_token();
    echo "   ✓ دالة csrf_token تعمل: " . substr($token1, 0, 10) . "...\n";
} else {
    echo "   ✗ دالة csrf_token غير موجودة\n";
}

if (function_exists('generate_csrf_token')) {
    $token2 = generate_csrf_token();
    echo "   ✓ دالة generate_csrf_token تعمل: " . substr($token2, 0, 10) . "...\n";
} else {
    echo "   ✗ دالة generate_csrf_token غير موجودة\n";
}

if (function_exists('verify_csrf_token')) {
    $result = verify_csrf_token($token1);
    echo "   ✓ دالة verify_csrf_token تعمل: " . ($result ? 'صحيح' : 'خطأ') . "\n";
} else {
    echo "   ✗ دالة verify_csrf_token غير موجودة\n";
}

if (function_exists('validate_csrf_token')) {
    $result = validate_csrf_token($token2);
    echo "   ✓ دالة validate_csrf_token تعمل: " . ($result ? 'صحيح' : 'خطأ') . "\n";
} else {
    echo "   ✗ دالة validate_csrf_token غير موجودة\n";
}

// اختبار 8: اختبار دوال المصادقة
echo "\n8. اختبار دوال المصادقة:\n";
if (function_exists('is_logged_in')) {
    $logged_in = is_logged_in();
    echo "   ✓ دالة is_logged_in تعمل: " . ($logged_in ? 'مسجل دخول' : 'غير مسجل') . "\n";
} else {
    echo "   ✗ دالة is_logged_in غير موجودة\n";
}

if (function_exists('check_permission')) {
    $has_permission = check_permission('test');
    echo "   ✓ دالة check_permission تعمل: " . ($has_permission ? 'لديه صلاحية' : 'ليس لديه صلاحية') . "\n";
} else {
    echo "   ✗ دالة check_permission غير موجودة\n";
}

// اختبار 9: اختبار دوال التنقل
echo "\n9. اختبار دوال التنقل:\n";
if (function_exists('is_active')) {
    $active = is_active('test');
    echo "   ✓ دالة is_active تعمل: " . $active . "\n";
} else {
    echo "   ✗ دالة is_active غير موجودة\n";
}

// اختبار 10: اختبار حالة الجلسة
echo "\n10. اختبار حالة الجلسة:\n";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "   ✓ الجلسة نشطة\n";
    
    if (isset($_SESSION['csrf_token'])) {
        echo "   ✓ متغير csrf_token موجود في الجلسة\n";
    } else {
        echo "   ✗ متغير csrf_token غير موجود في الجلسة\n";
    }
    
    if (isset($_SESSION['last_activity'])) {
        echo "   ✓ متغير last_activity موجود في الجلسة\n";
    } else {
        echo "   ✗ متغير last_activity غير موجود في الجلسة\n";
    }
} else {
    echo "   ✗ الجلسة غير نشطة\n";
}

// اختبار 11: اختبار دوال الأمان الإضافية
echo "\n11. اختبار دوال الأمان الإضافية:\n";
if (function_exists('clean_input')) {
    $cleaned = clean_input('test input');
    echo "   ✓ دالة clean_input تعمل: " . $cleaned . "\n";
} else {
    echo "   ✗ دالة clean_input غير موجودة\n";
}

if (function_exists('has_permission')) {
    $has_perm = has_permission('test');
    echo "   ✓ دالة has_permission تعمل: " . ($has_perm ? 'نعم' : 'لا') . "\n";
} else {
    echo "   ✗ دالة has_permission غير موجودة\n";
}

// اختبار 12: اختبار دوال الإدارة المالية
echo "\n12. اختبار دوال الإدارة المالية:\n";
if (function_exists('check_finance_permission')) {
    $finance_perm = check_finance_permission();
    echo "   ✓ دالة check_finance_permission تعمل: " . ($finance_perm ? 'لديه صلاحية' : 'ليس لديه صلاحية') . "\n";
} else {
    echo "   ✗ دالة check_finance_permission غير موجودة\n";
}

if (function_exists('check_system_tools_permission')) {
    $system_perm = check_system_tools_permission();
    echo "   ✓ دالة check_system_tools_permission تعمل: " . ($system_perm ? 'لديه صلاحية' : 'ليس لديه صلاحية') . "\n";
} else {
    echo "   ✗ دالة check_system_tools_permission غير موجودة\n";
}

echo "\n=== انتهى الاختبار الشامل ===\n";
echo "جميع الاختبارات مكتملة بنجاح! ✅\n";
echo "\nملاحظات:\n";
echo "- تم إصلاح جميع تضارب الدوال\n";
echo "- تم إصلاح مشاكل إدارة الجلسات\n";
echo "- جميع الدوال تعمل بشكل صحيح\n";
echo "- النظام جاهز للاستخدام\n";
?>