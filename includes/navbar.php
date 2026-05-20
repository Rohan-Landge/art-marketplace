<?php
/**
 * Navigation Bar Component
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/db.php';

$is_logged_in = is_authenticated();
$user_id = $is_logged_in ? get_user_id() : null;
$user_name = $is_logged_in ? ($_SESSION['user_name'] ?? '') : '';
$user_role = $is_logged_in ? ($_SESSION['user_role'] ?? 'buyer') : '';
$is_admin_user = is_admin();
$is_artist_user = is_artist();
$unread_count = 0;
$notifications = [];

if ($is_logged_in) {
    $unread_count = get_unread_notifications_count($pdo, $user_id);
    $notifications = get_recent_notifications($pdo, $user_id, 5);
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm sticky-top">
    <div class="container-fluid">
        <!-- Brand -->
        <a class="navbar-brand fw-bold" href="/art-marketplace/index.php">
            <i class="bi bi-palette-fill"></i> Art Marketplace
        </a>
        
        <!-- Toggle Button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Navigation Items -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <!-- Gallery Link (Everyone) -->
                <li class="nav-item">
                    <a class="nav-link" href="/art-marketplace/gallery/view_all.php">
                        <i class="bi bi-images"></i> Gallery
                    </a>
                </li>
                
                <!-- Admin Links (Only for admins) -->
                <?php if ($is_admin_user): ?>
                <li class="nav-item">
                    <a class="nav-link" href="/art-marketplace/admin/dashboard.php">
                        <i class="bi bi-speedometer2"></i> Admin Dashboard
                    </a>
                </li>
                <?php endif; ?>
                
                <!-- Artist Links (Only for artists) -->
                <?php if ($is_artist_user): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="artistDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-briefcase-fill"></i> Artist
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="artistDropdown">
                        <li><a class="dropdown-item" href="/art-marketplace/artist/dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a></li>
                        <li><a class="dropdown-item" href="/art-marketplace/artist/my_art.php">
                            <i class="bi bi-collection"></i> My Artworks
                        </a></li>
                        <li><a class="dropdown-item" href="/art-marketplace/artist/upload_art.php">
                            <i class="bi bi-cloud-upload"></i> Upload Art
                        </a></li>
                        <li><a class="dropdown-item" href="/art-marketplace/orders/artist_orders.php">
                            <i class="bi bi-box-seam"></i> My Orders
                        </a></li>
                    </ul>
                </li>
                <?php endif; ?>
                
                <!-- Wishlist Link (Logged in users) -->
                <?php if ($is_logged_in && !$is_admin_user): ?>
                <li class="nav-item">
                    <a class="nav-link" href="/art-marketplace/wishlist/index.php">
                        <i class="bi bi-heart-fill"></i> Wishlist
                    </a>
                </li>
                <?php endif; ?>
                
                <!-- Notifications (Logged in users) -->
                <?php if ($is_logged_in): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link position-relative" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-bell-fill"></i> Notifications
                        <?php if ($unread_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo min($unread_count, 99); ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown" style="width: 320px; max-height: 400px; overflow-y: auto;">
                        <?php if (empty($notifications)): ?>
                            <li><a class="dropdown-item disabled">No notifications</a></li>
                        <?php else: ?>
                            <?php foreach ($notifications as $notif): ?>
                                <li>
                                    <a class="dropdown-item <?php echo !$notif['is_read'] ? 'bg-light' : ''; ?>" 
                                       href="<?php echo escape($notif['link'] ?? '#'); ?>"
                                       onclick="markNotificationRead(<?php echo escape($notif['id']); ?>)">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong><?php echo escape($notif['title']); ?></strong>
                                                <div class="small text-muted"><?php echo escape(substr($notif['message'] ?? '', 0, 50)); ?>...</div>
                                            </div>
                                            <small class="text-muted"><?php echo escape(format_datetime($notif['created_at'])); ?></small>
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center" href="#">View All Notifications</a></li>
                    </ul>
                </li>
                <?php endif; ?>
                
                <!-- User Menu -->
                <?php if ($is_logged_in): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?php echo escape($user_name); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <?php if (!$is_admin_user): ?>
                            <li><a class="dropdown-item" href="/art-marketplace/orders/my_orders.php">
                                <i class="bi bi-box-seam"></i> My Orders
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="/art-marketplace/auth/logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a></li>
                    </ul>
                </li>
                <?php else: ?>
                <!-- Login/Register Links -->
                <li class="nav-item">
                    <a class="nav-link" href="/art-marketplace/auth/login.php">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/art-marketplace/auth/register.php">
                        <i class="bi bi-person-plus"></i> Register
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<script>
function markNotificationRead(notificationId) {
    // AJAX call to mark notification as read
    fetch('/art-marketplace/ajax/mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + notificationId
    }).catch(err => console.log(err));
}
</script>
