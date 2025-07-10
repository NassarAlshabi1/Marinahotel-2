<?php
/**
 * مدير الجلسات المبسط
 * يوفر إدارة بسيطة للجلسات في النظام
 */

/**
 * بدء الجلسة البسيط
 */
function start_simple_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * التحقق من تسجيل دخول المستخدم
 * @return bool
 */
function is_user_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * تسجيل دخول المستخدم
 * @param array $user_data بيانات المستخدم
 */
function login_user($user_data) {
    $_SESSION['user_id'] = $user_data['user_id'];
    $_SESSION['username'] = $user_data['username'];
    $_SESSION['user_type'] = $user_data['user_type'];
    $_SESSION['initiated'] = true;
}

/**
 * تسجيل خروج المستخدم
 * @param string $redirect_url رابط إعادة التوجيه (اختياري)
 */
function logout_user($redirect_url = '../login.php') {
    session_destroy();
    
    header("Location: $redirect_url?message=" . urlencode("تم تسجيل الخروج بنجاح"));
    exit;
}

/**
 * إعادة توجيه المستخدم إذا لم يكن مسجل دخول
 * @param string $login_url رابط صفحة تسجيل الدخول
 */
function redirect_if_not_logged_in($login_url = '../login.php') {
    if (!is_user_logged_in()) {
        header("Location: $login_url?error=" . urlencode("يجب تسجيل الدخول للوصول إلى هذه الصفحة"));
        exit;
    }
}

// بدء الجلسة البسيطة تلقائياً عند تضمين هذا الملف
start_simple_session();
?>
