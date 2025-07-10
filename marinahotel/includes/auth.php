<?php
/**
 * نظام المصادقة المبسط
 */

// بدء الجلسة إذا لم تكن بدأت بالفعل
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// التحقق من تسجيل دخول المستخدم
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// إعادة توجيه المستخدم إذا لم يكن مسجل دخول
function redirect_if_not_logged_in() {
    if (!is_logged_in()) {
        // تحديد المسار الصحيح لصفحة تسجيل الدخول
        $current_path = $_SERVER['REQUEST_URI'];
        $path_depth = substr_count(dirname($_SERVER['SCRIPT_NAME']), '/') - 1;
        $login_path = str_repeat('../', max(0, $path_depth)) . 'login.php';

        header("Location: " . $login_path . "?error=" . urlencode("يجب تسجيل الدخول للوصول إلى هذه الصفحة"));
        exit;
    }
}

// تسجيل خروج بسيط
function logout() {
    session_destroy();
    header("Location: ../login.php?message=" . urlencode("تم تسجيل الخروج بنجاح"));
    exit;
}

// التحقق من تسجيل دخول المستخدم
redirect_if_not_logged_in();
?>
