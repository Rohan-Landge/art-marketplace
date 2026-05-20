<?php
/**
 * Wishlist / Favorites Page
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

// Require authentication
require_auth();

$user_id = get_user_id();
$message = '';
$message_type = 'info';

// Handle remove from wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = sanitize($_POST['action']);
    $artwork_id = (int) $_POST['artwork_id'];
    
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Security verification failed.';
        $message_type = 'danger';
    } elseif ($action === 'remove') {
        try {
            $stmt = $pdo->prepare('DELETE FROM wishlist WHERE user_id = ? AND artwork_id = ?');
            $stmt->execute([$user_id, $artwork_id]);
            $message = 'Removed from wishlist.';
            $message_type = 'success';
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $message = 'Error removing from wishlist.';
            $message_type = 'danger';
        }
    }
}

// Get user's wishlist
try {
    $stmt = $pdo->prepare('
        SELECT a.*, u.name as artist_name, u.id as artist_id,
               (SELECT AVG(rating) FROM reviews WHERE artwork_id = a.id) as avg_rating,
               (SELECT COUNT(*) FROM reviews WHERE artwork_id = a.id) as review_count
        FROM wishlist w
        JOIN artworks a ON w.artwork_id = a.id
        JOIN users u ON a.user_id = u.id
        WHERE w.user_id = ?
        ORDER BY w.created_at DESC
    ');
    $stmt->execute([$user_id]);
    $wishlist_items = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $wishlist_items = [];
}

$page_title = 'My Wishlist';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3"><i class="bi bi-heart-fill"></i> My Wishlist</h1>
        </div>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo escape($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (empty($wishlist_items)): ?>
        <div class="text-center py-5">
            <i class="bi bi-heart" style="font-size: 4rem; color: #ddd;"></i>
            <h3 class="mt-3">Your wishlist is empty</h3>
            <p class="text-muted">Explore our gallery and add artworks to your wishlist!</p>
            <a href="/art-marketplace/gallery/view_all.php" class="btn btn-primary">
                <i class="bi bi-images"></i> Explore Gallery
            </a>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($wishlist_items as $item): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm hover-shadow">
                        <!-- Artwork Image -->
                        <div class="position-relative">
                            <img src="<?php echo escape($item['image']); ?>" 
                                 alt="<?php echo escape($item['title']); ?>" 
                                 class="card-img-top" 
                                 style="height: 250px; object-fit: cover;">
                            
                            <!-- Wishlist Remove Button -->
                            <form method="POST" style="position: absolute; top: 10px; right: 10px;">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="artwork_id" value="<?php echo escape($item['id']); ?>">
                                <button type="submit" class="btn btn-danger btn-sm rounded-circle" 
                                        style="width: 40px; height: 40px; padding: 0;" 
                                        onclick="return confirm('Remove from wishlist?');">
                                    <i class="bi bi-heart-fill"></i>
                                </button>
                            </form>
                        </div>
                        
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">
                                <a href="/art-marketplace/gallery/view_single.php?id=<?php echo escape($item['id']); ?>" 
                                   class="text-decoration-none">
                                    <?php echo escape($item['title']); ?>
                                </a>
                            </h5>
                            
                            <p class="card-text text-muted small">
                                By <strong><?php echo escape($item['artist_name']); ?></strong>
                            </p>
                            
                            <!-- Rating -->
                            <?php if ($item['avg_rating']): ?>
                                <div class="mb-2">
                                    <span class="text-warning">
                                        <i class="bi bi-star-fill"></i>
                                        <?php echo escape(number_format($item['avg_rating'], 1)); ?>
                                    </span>
                                    <span class="text-muted small">(<?php echo escape($item['review_count']); ?> reviews)</span>
                                </div>
                            <?php endif; ?>
                            
                            <p class="text-muted small mb-3">
                                <?php echo escape(substr($item['description'], 0, 80)); ?>...
                            </p>
                            
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="h5 text-success mb-0">
                                        <?php echo escape(format_currency($item['price'])); ?>
                                    </span>
                                    <a href="/art-marketplace/gallery/view_single.php?id=<?php echo escape($item['id']); ?>" 
                                       class="btn btn-outline-primary btn-sm">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.hover-shadow {
    transition: box-shadow 0.3s ease, transform 0.3s ease;
}
.hover-shadow:hover {
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15) !important;
    transform: translateY(-5px);
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
