<?php
/**
 * Delete Artwork Handler
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/auth_check.php';

// Require artist authentication
requireArtist();

$user_id = getCurrentUserId();
$artwork_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($artwork_id <= 0) {
    $_SESSION['error_message'] = 'Invalid artwork ID.';
    header('Location: /art-marketplace/artist/my_art.php');
    exit();
}

try {
    // Verify ownership and get artwork info
    $stmt = $pdo->prepare('SELECT id, image FROM artworks WHERE id = ? AND user_id = ?');
    $stmt->execute([$artwork_id, $user_id]);
    
    if ($stmt->rowCount() === 0) {
        $_SESSION['error_message'] = 'Artwork not found or you do not have permission to delete it.';
        header('Location: /art-marketplace/artist/my_art.php');
        exit();
    }
    
    $artwork = $stmt->fetch();
    
    // Delete image file
    $image_path = __DIR__ . '/../' . ltrim($artwork['image'], '/');
    if (file_exists($image_path)) {
        unlink($image_path);
    }
    
    // Delete from database
    $stmt = $pdo->prepare('DELETE FROM artworks WHERE id = ? AND user_id = ?');
    $stmt->execute([$artwork_id, $user_id]);
    
    // Redirect with success message
    header('Location: /art-marketplace/artist/my_art.php?deleted=1');
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    $_SESSION['error_message'] = 'Error deleting artwork.';
    header('Location: /art-marketplace/artist/my_art.php');
}
exit();

?>
