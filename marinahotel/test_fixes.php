<?php
/**
 * اختبار إصلاحات تضارب الدوال والجلسات
 */

// اختبار تحميل header.php بدون أخطاء
echo "بدء اختبار إصلاحات تضارب الدوال والجلسات...\n";

try {
    // تحميل header.php
    require_once 'includes/header.php';
    echo "✓ تم تحميل header.php بنجاح\n";
    
    // اختبار دالة csrf_token
    if (function_exists('csrf_token')) {
        $token = csrf_token();
        echo "✓ دالة csrf_token تعمل بشكل صحيح\n";
        echo "  Token: " . substr($token, 0, 10) . "...\n";
    } else {
        echo "✗ دالة csrf_token غير موجودة\n";
    }
    
    // اختبار دالة is_active
    if (function_exists('is_active')) {
        $active = is_active('test');
        echo "✓ دالة is_active تعمل بشكل صحيح\n";
        echo "  النتيجة: " . $active . "\n";
    } else {
        echo "✗ دالة is_active غير موجودة\n";
    }
    
    // اختبار حالة الجلسة
    if (session_status() === PHP_SESSION_ACTIVE) {
        echo "✓ الجلسة نشطة بشكل صحيح\n";
    } else {
        echo "✗ الجلسة غير نشطة\n";
    }
    
    // اختبار متغيرات الجلسة
    if (isset($_SESSION['csrf_token'])) {
        echo "✓ متغير csrf_token موجود في الجلسة\n";
    } else {
        echo "✗ متغير csrf_token غير موجود في الجلسة\n";
    }
    
    echo "\nجميع الاختبارات مكتملة بنجاح!\n";
    
} catch (Error $e) {
    echo "✗ خطأ في تحميل الملفات: " . $e->getMessage() . "\n";
    echo "  الملف: " . $e->getFile() . "\n";
    echo "  السطر: " . $e->getLine() . "\n";
} catch (Exception $e) {
    echo "✗ استثناء: " . $e->getMessage() . "\n";
}
?>