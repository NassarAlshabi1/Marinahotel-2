<?php
// ملف معالجة رسائل الواتساب المعلقة
// يمكن تشغيله من cron job كل 5 دقائق

require_once 'includes/db.php';
require_once 'includes/functions.php';

echo "بدء معالجة رسائل الواتساب المعلقة...\n";
echo "الوقت: " . date('Y-m-d H:i:s') . "\n";

try {
    $result = process_pending_whatsapp_messages();
    
    echo "تم معالجة: {$result['processed']} رسالة\n";
    echo "تم إرسال: {$result['sent']} رسالة\n";
    echo "رسائل غير صالحة: {$result['invalid']} رسالة\n";
    echo "رسائل معلقة: {$result['pending']} رسالة\n";
    
    // إنشاء إشعار نظام بناءً على النتائج
    if ($result['sent'] > 0) {
        $notification_msg = "تم إرسال {$result['sent']} رسالة واتساب بنجاح";
        if ($result['invalid'] > 0) {
            $notification_msg .= " و{$result['invalid']} رسائل غير صالحة";
        }
        create_system_notification(
            "إرسال رسائل واتساب",
            $notification_msg,
            "success"
        );
    }
    
    // إنشاء إشعار للرسائل غير الصالحة
    if ($result['invalid'] > 0 && $result['sent'] == 0) {
        create_system_notification(
            "رسائل واتساب غير صالحة",
            "تم اكتشاف {$result['invalid']} رسالة غير صالحة (أرقام غير مطابقة للنزلاء)",
            "warning"
        );
    }
    
    // إنشاء ملف log مفصل
    $log_entry = date('Y-m-d H:i:s') . " - معالج: {$result['processed']}, مرسل: {$result['sent']}, غير صالح: {$result['invalid']}, معلق: {$result['pending']}\n";
    // استخدام طريقة آمنة للكتابة بدون exclusive lock
try {
    file_put_contents('logs/whatsapp_queue.log', $log_entry, FILE_APPEND);
} catch (Exception $e) {
    error_log("WhatsApp queue log write failed: " . $e->getMessage());
}
    
    echo "تمت المعالجة بنجاح\n";
    
} catch (Exception $e) {
    $error_msg = "خطأ في معالجة رسائل الواتساب: " . $e->getMessage();
    echo $error_msg . "\n";
    
    // تسجيل الخطأ
    error_log($error_msg);
    
    // إنشاء إشعار خطأ
    create_system_notification(
        "خطأ في إرسال الواتساب",
        $error_msg,
        "error"
    );
}

echo "انتهت المعالجة\n";
echo "================================\n";
?>