<?php
/**
 * ملف التحقق من تسجيل دخول المستخدم - مبسط
 */

// التأكد من بدء الجلسة
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// التحقق من تسجيل دخول المستخدم
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// إعادة توجيه المستخدم إلى صفحة تسجيل الدخول إذا لم يكن مسجل دخول
function redirect_if_not_logged_in() {
    if (!is_logged_in()) {
        // تحديد المسار النسبي للوصول إلى صفحة تسجيل الدخول
        $current_path = $_SERVER['REQUEST_URI'];
        $path_depth = substr_count($current_path, '/') - 1;
        $login_path = str_repeat('../', $path_depth) . 'login.php';
        
        if ($path_depth <= 0) {
            $login_path = '../login.php';
        }
        
        header("Location: " . $login_path . "?error=" . urlencode("يجب تسجيل الدخول للوصول إلى هذه الصفحة"));
        exit;
    }
}

// التحقق من تسجيل دخول المستخدم وإعادة توجيهه إذا لم يكن مسجل دخول
redirect_if_not_logged_in();
?>
