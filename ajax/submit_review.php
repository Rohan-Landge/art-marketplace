<?php
/**
 * AJAX: Submit Review
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

// Verify CSRF token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Security verification failed']);
    exit();
}

$user_id = get_user_id();
$artwork_id = (int) ($_POST['artwork_id'] ?? 0);
$rating = (int) ($_POST['rating'] ?? 0);
$review_text = sanitize($_POST['review_text'] ?? '');

// Validation
if (!$artwork_id || $rating < 1 || $rating > 5 || strlen($review_text) < 10) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

try {
    // Check if user purchased this artwork
    $stmt = $pdo->prepare('
        SELECT id FROM orders 
        WHERE artwork_id = ? AND user_id = ? AND payment_status = "paid"
        LIMIT 1
    ');
    $stmt->execute([$artwork_id, $user_id]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You must purchase this artwork to review it']);
        exit();
    }
    
    // Check if user already reviewed
    $stmt = $pdo->prepare('
        SELECT id FROM reviews 
        WHERE artwork_id = ? AND user_id = ?
        LIMIT 1
    ');
    $stmt->execute([$artwork_id, $user_id]);
    
    if ($stmt->rowCount() > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'You have already reviewed this artwork']);
        exit();
    }
    
    // Insert review
    $stmt = $pdo->prepare('
        INSERT INTO reviews (artwork_id, user_id, rating, review_text, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ');
    $stmt->execute([$artwork_id, $user_id, $rating, $review_text]);
    
    // Update artwork rating and review count
    $stmt = $pdo->prepare('
        SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews
        FROM reviews
        WHERE artwork_id = ?
    ');
    $stmt->execute([$artwork_id]);
    $stats = $stmt->fetch();
    
    $update = $pdo->prepare('
        UPDATE artworks 
        SET average_rating = ?, review_count = ?
        WHERE id = ?
    ');
    $update->execute([
        round($stats['avg_rating'], 2),
        $stats['total_reviews'],
        $artwork_id
    ]);
    
    // Create notification for artist
    $artwork = $pdo->prepare('SELECT user_id, title FROM artworks WHERE id = ?');
    $artwork->execute([$artwork_id]);
    $art_data = $artwork->fetch();
    
    create_notification(
        $pdo,
        $art_data['user_id'],
        'new_review',
        'New Review',
        escape($_SESSION['user_name']) . ' left a ' . $rating . '-star review on "' . escape($art_data['title']) . '"',
        $artwork_id,
        '/art-marketplace/gallery/view_single.php?id=' . $artwork_id
    );
    
    echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error submitting review']);
}

?>
