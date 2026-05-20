<?php
/**
 * Artist: My Orders
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

// Require artist authentication
require_artist();

$artist_id = get_user_id();
$message = '';
$message_type = 'info';

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = sanitize($_POST['action']);
    $order_id = (int) $_POST['order_id'];
    $new_status = sanitize($_POST['new_status'] ?? '');
    
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Security verification failed.';
        $message_type = 'danger';
    } elseif ($action === 'update_status' && !empty($new_status)) {
        try {
            $stmt = $pdo->prepare('UPDATE orders SET order_status = ? WHERE id = ? AND artist_id = ?');
            $stmt->execute([$new_status, $order_id, $artist_id]);
            
            // Create notification for buyer
            $stmt = $pdo->prepare('SELECT buyer_id FROM orders WHERE id = ?');
            $stmt->execute([$order_id]);
            $order = $stmt->fetch();
            
            if ($order) {
                create_notification($pdo, $order['buyer_id'], 'order_shipped', 
                    'Order Status Updated', 
                    'Your order status has been updated to ' . ucfirst($new_status),
                    $order_id
                );
            }
            
            $message = 'Order status updated successfully.';
            $message_type = 'success';
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $message = 'Error updating order status.';
            $message_type = 'danger';
        }
    }
}

// Get artist's orders
try {
    $stmt = $pdo->prepare('
        SELECT o.*, 
               a.title as artwork_title, a.image as artwork_image, a.price as artwork_price,
               buyer.name as buyer_name, buyer.email as buyer_email, buyer.id as buyer_id
        FROM orders o
        JOIN artworks a ON o.artwork_id = a.id
        JOIN users buyer ON o.buyer_id = buyer.id
        WHERE o.artist_id = ?
        ORDER BY o.created_at DESC
    ');
    $stmt->execute([$artist_id]);
    $orders = $stmt->fetchAll();
    
    // Get summary
    $total_earnings = get_artist_earnings($pdo, $artist_id);
    $total_sold = get_artist_sales_count($pdo, $artist_id);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $orders = [];
    $total_earnings = 0;
    $total_sold = 0;
}

$page_title = 'My Orders';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h3"><i class="bi bi-box-seam"></i> My Orders</h1>
        </div>
        <div class="col-md-6 text-end">
            <div class="card d-inline-block">
                <div class="card-body">
                    <small class="text-muted">Total Sold</small><br>
                    <span class="h5 text-primary"><?php echo escape($total_sold); ?> artworks</span>
                    <br>
                    <small class="text-muted">Total Earnings</small><br>
                    <span class="h5 text-success"><?php echo escape(format_currency($total_earnings)); ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo escape($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (empty($orders)): ?>
        <div class="text-center py-5">
            <i class="bi bi-box-seam" style="font-size: 4rem; color: #ddd;"></i>
            <h3 class="mt-3">No orders yet</h3>
            <p class="text-muted">Your artworks haven't been purchased yet. Upload more artworks to increase sales!</p>
            <a href="/art-marketplace/artist/upload_art.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Upload Artwork
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
                                            Buyer: <strong><?php echo escape($order['buyer_name']); ?></strong>
                                        </small>
                                    </p>
                                    <p class="mb-0">
                                        <small class="text-muted">
                                            <?php echo escape($order['buyer_email']); ?>
                                        </small>
                                    </p>
                                    <p class="mb-0 mt-2">
                                        <small class="text-muted">
                                            Order Date: <?php echo escape(format_datetime($order['created_at'])); ?>
                                        </small>
                                    </p>
                                </div>
                                
                                <!-- Status Update Form -->
                                <div class="col-md-2">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        <input type="hidden" name="order_id" value="<?php echo escape($order['id']); ?>">
                                        <input type="hidden" name="action" value="update_status">
                                        
                                        <select name="new_status" class="form-select form-select-sm" onchange="this.form.submit();">
                                            <option value="pending" <?php echo $order['order_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="paid" <?php echo $order['order_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                            <option value="processing" <?php echo $order['order_status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="shipped" <?php echo $order['order_status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                            <option value="delivered" <?php echo $order['order_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        </select>
                                    </form>
                                </div>
                                
                                <!-- Amount & Status -->
                                <div class="col-md-2 text-end">
                                    <h5 class="text-success mb-3">
                                        <?php echo escape(format_currency($order['amount'])); ?>
                                    </h5>
                                    <span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                        <?php echo escape(ucfirst($order['payment_status'])); ?>
                                    </span>
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
