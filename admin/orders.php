<?php
/**
 * Admin: Manage Orders
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

// Check admin access
require_admin();

$admin_id = get_user_id();
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
            $stmt = $pdo->prepare('UPDATE orders SET order_status = ? WHERE id = ?');
            $stmt->execute([$new_status, $order_id]);
            
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
            
            log_admin_action($pdo, $admin_id, 'update_order_status', 'orders', $order_id, ['status' => $new_status]);
            $message = 'Order status updated successfully.';
            $message_type = 'success';
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $message = 'Error updating order status.';
            $message_type = 'danger';
        }
    }
}

// Get all orders
try {
    $stmt = $pdo->prepare('
        SELECT o.*, 
               buyer.name as buyer_name, buyer.email as buyer_email,
               artist.name as artist_name, artist.email as artist_email,
               a.title as artwork_title, a.price as artwork_price
        FROM orders o
        JOIN users buyer ON o.buyer_id = buyer.id
        JOIN users artist ON o.artist_id = artist.id
        JOIN artworks a ON o.artwork_id = a.id
        ORDER BY o.created_at DESC
    ');
    $stmt->execute();
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $orders = [];
}

$page_title = 'Manage Orders';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid my-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3"><i class="bi bi-box-seam"></i> Manage Orders</h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo escape($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <?php if (empty($orders)): ?>
                <p class="text-muted">No orders found.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Order ID</th>
                                <th>Buyer</th>
                                <th>Artist</th>
                                <th>Artwork</th>
                                <th>Amount</th>
                                <th>Order Status</th>
                                <th>Payment Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><strong>#<?php echo escape($order['id']); ?></strong></td>
                                    <td>
                                        <?php echo escape($order['buyer_name']); ?>
                                        <br>
                                        <small class="text-muted"><?php echo escape($order['buyer_email']); ?></small>
                                    </td>
                                    <td>
                                        <?php echo escape($order['artist_name']); ?>
                                        <br>
                                        <small class="text-muted"><?php echo escape($order['artist_email']); ?></small>
                                    </td>
                                    <td><?php echo escape(substr($order['artwork_title'], 0, 25)); ?></td>
                                    <td><?php echo escape(format_currency($order['amount'])); ?></td>
                                    <td>
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
                                                <option value="cancelled" <?php echo $order['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <?php
                                        $payment_class = $order['payment_status'] === 'paid' ? 'success' : 'warning';
                                        ?>
                                        <span class="badge bg-<?php echo $payment_class; ?>">
                                            <?php echo escape(ucfirst($order['payment_status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo escape(format_date($order['created_at'])); ?></td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#orderModal<?php echo escape($order['id']); ?>">
                                            <i class="bi bi-info-circle"></i> Details
                                        </a>
                                    </td>
                                </tr>
                                
                                <!-- Order Details Modal -->
                                <div class="modal fade" id="orderModal<?php echo escape($order['id']); ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Order #<?php echo escape($order['id']); ?> Details</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <h6>Buyer Information</h6>
                                                        <p>
                                                            <strong>Name:</strong> <?php echo escape($order['buyer_name']); ?><br>
                                                            <strong>Email:</strong> <?php echo escape($order['buyer_email']); ?>
                                                        </p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h6>Shipping Address</h6>
                                                        <p>
                                                            <?php echo escape($order['shipping_address'] ?? 'Not provided'); ?><br>
                                                            <?php echo escape($order['shipping_city'] ?? ''); ?>, <?php echo escape($order['shipping_state'] ?? ''); ?> <?php echo escape($order['shipping_pincode'] ?? ''); ?>
                                                        </p>
                                                    </div>
                                                </div>
                                                <hr>
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <h6>Artwork</h6>
                                                        <p>
                                                            <strong><?php echo escape($order['artwork_title']); ?></strong><br>
                                                            Artist: <?php echo escape($order['artist_name']); ?>
                                                        </p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h6>Order Information</h6>
                                                        <p>
                                                            <strong>Amount:</strong> <?php echo escape(format_currency($order['amount'])); ?><br>
                                                            <strong>Order Date:</strong> <?php echo escape(format_datetime($order['created_at'])); ?>
                                                        </p>
                                                    </div>
                                                </div>
                                                <?php if ($order['tracking_number']): ?>
                                                    <hr>
                                                    <p>
                                                        <strong>Tracking Number:</strong> <?php echo escape($order['tracking_number']); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
