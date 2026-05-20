<?php
/**
 * Buyer: My Orders
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

// Require buyer authentication
require_auth();

$user_id = get_user_id();

// Get buyer's orders
try {
    $stmt = $pdo->prepare('
        SELECT o.*, 
               a.title as artwork_title, a.image as artwork_image, a.price as artwork_price,
               artist.name as artist_name, artist.id as artist_id
        FROM orders o
        JOIN artworks a ON o.artwork_id = a.id
        JOIN users artist ON o.artist_id = artist.id
        WHERE o.buyer_id = ?
        ORDER BY o.created_at DESC
    ');
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $orders = [];
}

$page_title = 'My Orders';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3"><i class="bi bi-box-seam"></i> My Orders</h1>
        </div>
    </div>
    
    <?php if (empty($orders)): ?>
        <div class="text-center py-5">
            <i class="bi bi-box-seam" style="font-size: 4rem; color: #ddd;"></i>
            <h3 class="mt-3">No orders yet</h3>
            <p class="text-muted">Start exploring and purchasing artworks!</p>
            <a href="/art-marketplace/gallery/view_all.php" class="btn btn-primary">
                <i class="bi bi-images"></i> Explore Gallery
            </a>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($orders as $order): ?>
                <div class="col-12 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <!-- Artwork Image -->
                                <div class="col-md-2">
                                    <img src="<?php echo escape($order['artwork_image']); ?>" 
                                         alt="<?php echo escape($order['artwork_title']); ?>" 
                                         class="img-fluid rounded" 
                                         style="max-height: 150px; object-fit: cover;">
                                </div>
                                
                                <!-- Order Details -->
                                <div class="col-md-6">
                                    <h5 class="mb-2">
                                        <a href="/art-marketplace/gallery/view_single.php?id=<?php echo escape($order['artwork_id']); ?>" 
                                           class="text-decoration-none">
                                            <?php echo escape($order['artwork_title']); ?>
                                        </a>
                                    </h5>
                                    <p class="mb-1">
                                        <small class="text-muted">
                                            By <strong><?php echo escape($order['artist_name']); ?></strong>
                                        </small>
                                    </p>
                                    <p class="mb-0">
                                        <small class="text-muted">
                                            Order Date: <?php echo escape(format_datetime($order['created_at'])); ?>
                                        </small>
                                    </p>
                                    
                                    <?php if ($order['shipping_address']): ?>
                                        <p class="mb-0 mt-2">
                                            <small class="text-muted">
                                                <i class="bi bi-geo-alt"></i>
                                                <?php echo escape($order['shipping_city'] ?? '') . ', ' . escape($order['shipping_state'] ?? ''); ?>
                                            </small>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Status -->
                                <div class="col-md-2 text-center">
                                    <div class="mb-2">
                                        <span class="badge bg-info">
                                            <?php echo escape(ucfirst($order['order_status'])); ?>
                                        </span>
                                    </div>
                                    <div>
                                        <span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                            <?php echo escape(ucfirst($order['payment_status'])); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Amount & Action -->
                                <div class="col-md-2 text-end">
                                    <h5 class="text-success mb-3">
                                        <?php echo escape(format_currency($order['amount'])); ?>
                                    </h5>
                                    <a href="/art-marketplace/gallery/view_single.php?id=<?php echo escape($order['artwork_id']); ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> View
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
