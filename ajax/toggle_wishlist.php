<?php
/**
 * AJAX: Toggle Wishlist
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

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
$artwork_id = (int) ($_POST['artwork_id'] ?? 0);

if (!$artwork_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid artwork ID']);
    exit();
}

try {
    // Check if already in wishlist
    $is_wishlisted = is_in_wishlist($pdo, $user_id, $artwork_id);
    
    if ($is_wishlisted) {
        // Remove from wishlist
        $stmt = $pdo->prepare('DELETE FROM wishlist WHERE user_id = ? AND artwork_id = ?');
        $stmt->execute([$user_id, $artwork_id]);
        
        // Decrement favorite count
        $update = $pdo->prepare('UPDATE artworks SET favorite_count = GREATEST(0, favorite_count - 1) WHERE id = ?');
        $update->execute([$artwork_id]);
        
        echo json_encode([
            'success' => true,
            'action' => 'removed',
            'message' => 'Removed from wishlist',
            'wishlisted' => false
        ]);
    } else {
        // Add to wishlist
        $stmt = $pdo->prepare('INSERT INTO wishlist (user_id, artwork_id) VALUES (?, ?)');
        $stmt->execute([$user_id, $artwork_id]);
        
        // Increment favorite count
        $update = $pdo->prepare('UPDATE artworks SET favorite_count = favorite_count + 1 WHERE id = ?');
        $update->execute([$artwork_id]);
        
        echo json_encode([
            'success' => true,
            'action' => 'added',
            'message' => 'Added to wishlist',
            'wishlisted' => true
        ]);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error updating wishlist']);
}

?>
