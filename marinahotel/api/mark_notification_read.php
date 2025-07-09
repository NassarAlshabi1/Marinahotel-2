<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/functions.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $notification_id = intval($input['id'] ?? 0);
    
    if ($notification_id <= 0) {
        throw new Exception('معرف الإشعار غير صالح');
    }
    
    $result = mark_notification_as_read($notification_id);
    
    echo json_encode([
        'success' => $result,
        'message' => $result ? 'تم وضع علامة مقروء' : 'فشل في تحديث الإشعار'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>