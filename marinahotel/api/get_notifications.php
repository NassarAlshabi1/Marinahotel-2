<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/functions.php';

try {
    $user_id = $_SESSION['user_id'] ?? null;
    $notifications = get_unread_notifications($user_id, 10);
    
    echo json_encode([
        'success' => true,
        'count' => count($notifications),
        'notifications' => $notifications
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'count' => 0,
        'notifications' => []
    ]);
}
?>