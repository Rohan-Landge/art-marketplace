<?php
/**
 * AJAX: Mark Notification as Read
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

// Require authentication
if (!is_authenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$user_id = get_user_id();
$notification_id = (int) ($_POST['notification_id'] ?? 0);

if (!$notification_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    exit();
}

try {
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
    $stmt->execute([$notification_id, $user_id]);
    
    echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error updating notification']);
}

?>
