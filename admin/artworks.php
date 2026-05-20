<?php
/**
 * Admin: Manage Artworks
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

// Handle delete artwork
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = sanitize($_POST['action']);
    $artwork_id = (int) $_POST['artwork_id'];
    
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Security verification failed.';
        $message_type = 'danger';
    } else {
        try {
            if ($action === 'delete') {
                // Get artwork details
                $stmt = $pdo->prepare('SELECT * FROM artworks WHERE id = ?');
                $stmt->execute([$artwork_id]);
                $artwork = $stmt->fetch();
                
                if ($artwork) {
                    // Delete from database
                    $stmt = $pdo->prepare('DELETE FROM artworks WHERE id = ?');
                    $stmt->execute([$artwork_id]);
                    
                    // Delete image file
                    $image_path = $_SERVER['DOCUMENT_ROOT'] . '/art-marketplace/uploads/artworks/' . $artwork['image'];
                    if (file_exists($image_path)) {
                        unlink($image_path);
                    }
                    
                    log_admin_action($pdo, $admin_id, 'delete_artwork', 'artworks', $artwork_id);
                    $message = 'Artwork deleted successfully.';
                    $message_type = 'success';
                }
            } elseif ($action === 'deactivate') {
                $stmt = $pdo->prepare('UPDATE artworks SET status = "inactive" WHERE id = ?');
                $stmt->execute([$artwork_id]);
                log_admin_action($pdo, $admin_id, 'deactivate_artwork', 'artworks', $artwork_id);
                $message = 'Artwork deactivated.';
                $message_type = 'success';
            } elseif ($action === 'activate') {
                $stmt = $pdo->prepare('UPDATE artworks SET status = "active" WHERE id = ?');
                $stmt->execute([$artwork_id]);
                log_admin_action($pdo, $admin_id, 'activate_artwork', 'artworks', $artwork_id);
                $message = 'Artwork activated.';
                $message_type = 'success';
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $message = 'Error performing action.';
            $message_type = 'danger';
        }
    }
}

// Get all artworks
try {
    $stmt = $pdo->prepare('
        SELECT a.*, u.name as artist_name, u.email as artist_email
        FROM artworks a
        JOIN users u ON a.user_id = u.id
        ORDER BY a.created_at DESC
    ');
    $stmt->execute();
    $artworks = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $artworks = [];
}

$page_title = 'Manage Artworks';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid my-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3"><i class="bi bi-images"></i> Manage Artworks</h1>
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
            <?php if (empty($artworks)): ?>
                <p class="text-muted">No artworks found.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Artist</th>
                                <th>Price</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Views</th>
                                <th>Rating</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($artworks as $artwork): ?>
                                <tr>
                                    <td><?php echo escape($artwork['id']); ?></td>
                                    <td>
                                        <strong><?php echo escape(substr($artwork['title'], 0, 30)); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo escape($artwork['artist_name']); ?>
                                        <br>
                                        <small class="text-muted"><?php echo escape($artwork['artist_email']); ?></small>
                                    </td>
                                    <td><?php echo escape(format_currency($artwork['price'])); ?></td>
                                    <td><?php echo escape($artwork['category'] ?? 'Uncategorized'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $artwork['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo escape(ucfirst($artwork['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <i class="bi bi-eye"></i> <?php echo escape($artwork['view_count']); ?>
                                    </td>
                                    <td>
                                        <?php if ($artwork['average_rating'] > 0): ?>
                                            <i class="bi bi-star-fill text-warning"></i>
                                            <?php echo escape(number_format($artwork['average_rating'], 1)); ?>
                                        <?php else: ?>
                                            <text class="text-muted">No ratings</text>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo escape(format_date($artwork['created_at'])); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="artwork_id" value="<?php echo escape($artwork['id']); ?>">
                                            
                                            <a href="/art-marketplace/gallery/view_single.php?id=<?php echo escape($artwork['id']); ?>" 
                                               class="btn btn-sm btn-info" target="_blank">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            
                                            <?php if ($artwork['status'] === 'active'): ?>
                                                <button type="submit" name="action" value="deactivate" class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pause"></i> Deactivate
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" name="action" value="activate" class="btn btn-sm btn-success">
                                                    <i class="bi bi-play"></i> Activate
                                                </button>
                                            <?php endif; ?>
                                            
                                            <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('Delete this artwork?');">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
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
