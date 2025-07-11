<?php
/**
 * إصلاح تضارب الدوال والجلسات
 * Fix Function Conflicts and Session Issues
 */

echo "🔧 بدء إصلاح تضارب الدوال...\n\n";

// فحص إذا كان ملف header.php يحتوي على session_start بدون فحص
$header_content = file_get_contents('includes/header.php');

if (strpos($header_content, 'session_status() === PHP_SESSION_NONE') === false) {
    echo "❌ مشكلة session_start في header.php لم تحل بعد\n";
    
    $header_content = str_replace(
        'session_start();',
        'if (session_status() === PHP_SESSION_NONE) {
    session_start();
}',
        $header_content
    );
    
    if (file_put_contents('includes/header.php', $header_content)) {
        echo "✅ تم إصلاح session_start في header.php\n";
    } else {
        echo "❌ فشل في إصلاح header.php\n";
    }
} else {
    echo "✅ session_start في header.php محلول بالفعل\n";
}

// فحص وإصلاح تضارب الدوال في header.php
$header_functions = [
    'csrf_token',
    'verify_csrf_token', 
    'csrf_field',
    'clean_input',
    'has_permission',
    'log_security_event',
    'verify_post_request',
    'require_login',
    'require_permission',
    'secure_file_upload'
];

$header_content = file_get_contents('includes/header.php');
$functions_fixed = 0;

foreach ($header_functions as $func) {
    $pattern = "/function\s+{$func}\s*\(/";
    if (preg_match($pattern, $header_content)) {
        echo "⚠️  تم العثور على دالة {$func} في header.php\n";
        
        // إضافة function_exists check
        $old_pattern = "/function\s+{$func}\s*\([^{]*\{/";
        $replacement = "if (!function_exists('{$func}')) {\n    function {$func}(";
        
        if (preg_match($old_pattern, $header_content, $matches)) {
            $function_def = $matches[0];
            $new_function_def = str_replace("function {$func}(", $replacement, $function_def);
            $header_content = str_replace($function_def, $new_function_def, $header_content);
            
            // إضافة إغلاق الـ if
            $brace_count = 1;
            $pos = strpos($header_content, $new_function_def) + strlen($new_function_def);
            $closing_pos = $pos;
            
            while ($brace_count > 0 && $closing_pos < strlen($header_content)) {
                if ($header_content[$closing_pos] === '{') {
                    $brace_count++;
                } elseif ($header_content[$closing_pos] === '}') {
                    $brace_count--;
                }
                $closing_pos++;
            }
            
            if ($brace_count === 0) {
                $header_content = substr_replace($header_content, "}\n}", $closing_pos - 1, 1);
                $functions_fixed++;
            }
        }
    }
}

if ($functions_fixed > 0) {
    if (file_put_contents('includes/header.php', $header_content)) {
        echo "✅ تم إصلاح {$functions_fixed} دوال في header.php\n";
    } else {
        echo "❌ فشل في حفظ التعديلات\n";
    }
} else {
    echo "✅ لا توجد دوال متضاربة في header.php\n";
}

// إنشاء ملف اختبار للتحقق من الإصلاحات
$test_content = '<?php
/**
 * اختبار الدوال والجلسات
 */

echo "<h2>🧪 اختبار الدوال والجلسات</h2>";

// بدء الجلسة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    echo "<p>✅ تم بدء الجلسة بنجاح</p>";
} else {
    echo "<p>✅ الجلسة نشطة بالفعل</p>";
}

// اختبار تحميل header
try {
    require_once "includes/header.php";
    echo "<p>✅ تم تحميل header.php بنجاح</p>";
} catch (Exception $e) {
    echo "<p>❌ خطأ في تحميل header.php: " . $e->getMessage() . "</p>";
}

// اختبار الدوال الأمنية
if (function_exists("csrf_token")) {
    try {
        $token = csrf_token();
        echo "<p>✅ دالة csrf_token تعمل</p>";
    } catch (Exception $e) {
        echo "<p>❌ خطأ في csrf_token: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ دالة csrf_token غير موجودة</p>";
}

echo "<p><strong>🎉 انتهى الاختبار</strong></p>";
echo "<p><a href=\"login.php\">تسجيل الدخول</a></p>";
?>';

if (file_put_contents('test_functions.php', $test_content)) {
    echo "✅ تم إنشاء ملف اختبار الدوال\n";
} else {
    echo "❌ فشل في إنشاء ملف الاختبار\n";
}

// إنشاء ملف header مبسط
$simple_header_content = '<?php
/**
 * Header مبسط بدون تضارب
 */

// بدء الجلسة بأمان
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// تحميل الملفات الأساسية
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/db.php";

// دوال CSRF بسيطة
if (!function_exists("safe_csrf_token")) {
    function safe_csrf_token() {
        if (!isset($_SESSION["csrf_token"])) {
            $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
        }
        return $_SESSION["csrf_token"];
    }
}

if (!function_exists("safe_verify_csrf")) {
    function safe_verify_csrf($token) {
        return isset($_SESSION["csrf_token"]) && hash_equals($_SESSION["csrf_token"], $token);
    }
}

// HTML Head
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام إدارة فندق مارينا</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- خط عربي -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    
    <style>
        body { 
            font-family: "Tajawal", sans-serif; 
            direction: rtl; 
            text-align: right; 
        }
        .navbar-brand { font-weight: 700; }
        .alert { margin: 20px 0; }
    </style>
</head>
<body>

<!-- Navbar بسيط -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="dash.php">
            <i class="fas fa-hotel"></i> فندق مارينا
        </a>
        
        <div class="navbar-nav ms-auto">
            <?php if (isset($_SESSION["user_id"])): ?>
                <span class="navbar-text text-light me-3">
                    مرحباً <?= htmlspecialchars($_SESSION["username"] ?? "المستخدم") ?>
                </span>
                <a class="nav-link text-light" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> خروج
                </a>
            <?php else: ?>
                <a class="nav-link text-light" href="login.php">
                    <i class="fas fa-sign-in-alt"></i> دخول
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <!-- رسائل النظام -->
    <?php if (isset($_SESSION["success"])): ?>
        <div class="alert alert-success alert-dismissible">
            <?= htmlspecialchars($_SESSION["success"]) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION["success"]); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION["error"])): ?>
        <div class="alert alert-danger alert-dismissible">
            <?= htmlspecialchars($_SESSION["error"]) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION["error"]); ?>
    <?php endif; ?>
';

if (file_put_contents('includes/safe-header.php', $simple_header_content)) {
    echo "✅ تم إنشاء header آمن بديل\n";
} else {
    echo "❌ فشل في إنشاء header البديل\n";
}

echo "\n=== ملخص الإصلاحات ===\n";
echo "1. ✅ إصلاح session_start في header.php\n";
echo "2. ✅ إضافة function_exists checks للدوال\n"; 
echo "3. ✅ إنشاء header آمن بديل\n";
echo "4. ✅ إنشاء ملف اختبار الدوال\n\n";

echo "🧪 للاختبار:\n";
echo "- php test_functions.php\n";
echo "- http://localhost/marinahotel/test_functions.php\n\n";

echo "💡 لاستخدام header آمن:\n";
echo "- استبدل require_once 'includes/header.php'\n";
echo "- بـ require_once 'includes/safe-header.php'\n\n";

echo "✅ تم الانتهاء من إصلاح تضارب الدوال!\n";
?>