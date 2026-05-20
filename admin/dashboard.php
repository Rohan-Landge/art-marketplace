<?php
/**
 * Admin Dashboard
 * Central hub for platform management and analytics
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

// Check admin access
if (!is_authenticated() || !is_admin()) {
    http_response_code(403);
    die('Access Denied. Admin privileges required.');
}

$admin_id = get_user_id();

// Get dashboard metrics
try {
    // Total users
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM users WHERE is_blocked = 0');
    $stmt->execute();
    $total_users = $stmt->fetch()['count'];
    
    // Total artists
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM users WHERE role = "artist" AND is_blocked = 0');
    $stmt->execute();
    $total_artists = $stmt->fetch()['count'];
    
    // Total artworks
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM artworks WHERE status = "active"');
    $stmt->execute();
    $total_artworks = $stmt->fetch()['count'];
    
    // Total orders
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM orders');
    $stmt->execute();
    $total_orders = $stmt->fetch()['count'];
    
    // Total revenue
    $stmt = $pdo->prepare('SELECT SUM(amount) as total FROM orders WHERE payment_status = "paid"');
    $stmt->execute();
    $total_revenue = $stmt->fetch()['total'] ?? 0;
    
    // Recent orders
    $stmt = $pdo->prepare('
        SELECT o.*, u.name as buyer_name, a.title as artwork_title, artist.name as artist_name
        FROM orders o
        JOIN users u ON o.buyer_id = u.id
        JOIN artworks a ON o.artwork_id = a.id
        JOIN users artist ON o.artist_id = artist.id
        ORDER BY o.created_at DESC
        LIMIT 10
    ');
    $stmt->execute();
    $recent_orders = $stmt->fetchAll();
    
    // Pending orders
    $stmt = $pdo->prepare('
        SELECT COUNT(*) as count FROM orders
        WHERE order_status = "pending" OR order_status = "paid"
    ');
    $stmt->execute();
    $pending_orders = $stmt->fetch()['count'];
    
    // Blocked users
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM users WHERE is_blocked = 1');
    $stmt->execute();
    $blocked_users = $stmt->fetch()['count'];
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'Error loading dashboard data.';
}

$page_title = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid my-5">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-0"><i class="bi bi-speedometer2"></i> Admin Dashboard</h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="users.php" class="btn btn-primary btn-sm"><i class="bi bi-people"></i> Manage Users</a>
            <a href="artworks.php" class="btn btn-primary btn-sm"><i class="bi bi-images"></i> Manage Artworks</a>
            <a href="orders.php" class="btn btn-primary btn-sm"><i class="bi bi-box-seam"></i> View Orders</a>
        </div>
    </div>
    
    <!-- Analytics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-left-primary">
                <div class="card-body">
                    <div class="text-primary mb-2">
                        <i class="bi bi-people-fill" style="font-size: 2rem;"></i>
                    </div>
                    <h5 class="card-title">Total Users</h5>
                    <h2 class="text-primary"><?php echo escape($total_users); ?></h2>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card border-left-success">
                <div class="card-body">
                    <div class="text-success mb-2">
                        <i class="bi bi-palette-fill" style="font-size: 2rem;"></i>
                    </div>
                    <h5 class="card-title">Total Artists</h5>
                    <h2 class="text-success"><?php echo escape($total_artists); ?></h2>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card border-left-warning">
                <div class="card-body">
                    <div class="text-warning mb-2">
                        <i class="bi bi-image" style="font-size: 2rem;"></i>
                    </div>
                    <h5 class="card-title">Total Artworks</h5>
                    <h2 class="text-warning"><?php echo escape($total_artworks); ?></h2>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card border-left-danger">
                <div class="card-body">
                    <div class="text-danger mb-2">
                        <i class="bi bi-box-seam" style="font-size: 2rem;"></i>
                    </div>
                    <h5 class="card-title">Total Orders</h5>
                    <h2 class="text-danger"><?php echo escape($total_orders); ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Revenue and Status Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-currency-rupee"></i> Total Revenue</h5>
                </div>
                <div class="card-body">
                    <h2 class="text-success"><?php echo escape(format_currency($total_revenue)); ?></h2>
                    <small class="text-muted">From completed transactions</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-hourglass-split"></i> Pending Orders</h5>
                </div>
                <div class="card-body">
                    <h2 class="text-warning"><?php echo escape($pending_orders); ?></h2>
                    <small class="text-muted">Awaiting processing or delivery</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-exclamation-circle"></i> Blocked Users</h5>
                </div>
                <div class="card-body">
                    <h2 class="text-danger"><?php echo escape($blocked_users); ?></h2>
                    <small class="text-muted">Users with access restrictions</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Orders Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Recent Orders</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_orders)): ?>
                        <p class="text-muted">No orders yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
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
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td><strong>#<?php echo escape($order['id']); ?></strong></td>
                                            <td><?php echo escape($order['buyer_name']); ?></td>
                                            <td><?php echo escape($order['artist_name']); ?></td>
                                            <td><?php echo escape(substr($order['artwork_title'], 0, 30)); ?>...</td>
                                            <td><?php echo escape(format_currency($order['amount'])); ?></td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo escape(ucfirst($order['order_status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $status_class = $order['payment_status'] === 'paid' ? 'success' : 'warning';
                                                ?>
                                                <span class="badge bg-<?php echo $status_class; ?>">
                                                    <?php echo escape(ucfirst($order['payment_status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo escape(format_date($order['created_at'])); ?></td>
                                            <td>
                                                <a href="orders.php?id=<?php echo escape($order['id']); ?>" class="btn btn-sm btn-info">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-primary {
    border-left: 4px solid #0d6efd;
}
.border-left-success {
    border-left: 4px solid #198754;
}
.border-left-warning {
    border-left: 4px solid #ffc107;
}
.border-left-danger {
    border-left: 4px solid #dc3545;
}
.card {
    transition: transform 0.2s, box-shadow 0.2s;
}
.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
