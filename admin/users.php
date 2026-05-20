<?php
/**
 * Admin: Manage Users
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

// Handle block/unblock user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = sanitize($_POST['action']);
    $user_id = (int) $_POST['user_id'];
    
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Security verification failed.';
        $message_type = 'danger';
    } elseif ($user_id === $admin_id) {
        $message = 'Cannot modify your own account.';
        $message_type = 'danger';
    } else {
        try {
            if ($action === 'block') {
                $stmt = $pdo->prepare('UPDATE users SET is_blocked = 1 WHERE id = ?');
                $stmt->execute([$user_id]);
                log_admin_action($pdo, $admin_id, 'block_user', 'users', $user_id);
                $message = 'User blocked successfully.';
                $message_type = 'success';
            } elseif ($action === 'unblock') {
                $stmt = $pdo->prepare('UPDATE users SET is_blocked = 0 WHERE id = ?');
                $stmt->execute([$user_id]);
                log_admin_action($pdo, $admin_id, 'unblock_user', 'users', $user_id);
                $message = 'User unblocked successfully.';
                $message_type = 'success';
            } elseif ($action === 'make_admin') {
                $stmt = $pdo->prepare('UPDATE users SET is_admin = 1 WHERE id = ?');
                $stmt->execute([$user_id]);
                log_admin_action($pdo, $admin_id, 'make_admin', 'users', $user_id);
                $message = 'User promoted to admin.';
                $message_type = 'success';
            } elseif ($action === 'remove_admin') {
                $stmt = $pdo->prepare('UPDATE users SET is_admin = 0 WHERE id = ?');
                $stmt->execute([$user_id]);
                log_admin_action($pdo, $admin_id, 'remove_admin', 'users', $user_id);
                $message = 'Admin privileges removed.';
                $message_type = 'success';
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $message = 'Error performing action.';
            $message_type = 'danger';
        }
    }
}

// Get all users
try {
    $stmt = $pdo->prepare('
        SELECT * FROM users
        ORDER BY created_at DESC
    ');
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $users = [];
}

$page_title = 'Manage Users';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid my-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3"><i class="bi bi-people"></i> Manage Users</h1>
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
            <?php if (empty($users)): ?>
                <p class="text-muted">No users found.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Admin</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo escape($user['id']); ?></td>
                                    <td><?php echo escape($user['name']); ?></td>
                                    <td><?php echo escape($user['email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['role'] === 'artist' ? 'primary' : 'secondary'; ?>">
                                            <?php echo escape(ucfirst($user['role'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user['is_blocked']): ?>
                                            <span class="badge bg-danger">Blocked</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['is_admin']): ?>
                                            <span class="badge bg-warning text-dark">Yes</span>
                                        <?php else: ?>
                                            <span class="text-muted">No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo escape(format_date($user['created_at'])); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="user_id" value="<?php echo escape($user['id']); ?>">
                                            
                                            <?php if ($user['is_blocked']): ?>
                                                <button type="submit" name="action" value="unblock" class="btn btn-sm btn-success" onclick="return confirm('Unblock this user?');">
                                                    <i class="bi bi-unlock"></i> Unblock
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" name="action" value="block" class="btn btn-sm btn-danger" onclick="return confirm('Block this user?');">
                                                    <i class="bi bi-lock"></i> Block
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($user['is_admin']): ?>
                                                <button type="submit" name="action" value="remove_admin" class="btn btn-sm btn-warning" onclick="return confirm('Remove admin privileges?');">
                                                    <i class="bi bi-shield-x"></i> Remove Admin
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" name="action" value="make_admin" class="btn btn-sm btn-primary" onclick="return confirm('Make this user admin?');">
                                                    <i class="bi bi-shield-check"></i> Make Admin
                                                </button>
                                            <?php endif; ?>
                                        </form>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
