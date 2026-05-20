<?php
/**
 * Artist - View My Artworks
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/auth_check.php';

// Require artist authentication
requireArtist();

$user_id = getCurrentUserId();
$page_title = 'My Artworks';

try {
    // Fetch all artworks for this artist
    $stmt = $pdo->prepare('
        SELECT * FROM artworks 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ');
    $stmt->execute([$user_id]);
    $artworks = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $artworks = [];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container my-5">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-collection"></i> My Artworks</h1>
        <a href="/art-marketplace/artist/upload_art.php" class="btn btn-primary">
            <i class="bi bi-cloud-upload"></i> Upload New
        </a>
    </div>
    
    <!-- Status Messages -->
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> Artwork deleted successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Artworks Table -->
    <?php if (count($artworks) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($artworks as $art): ?>
                        <tr>
                            <!-- Image Thumbnail -->
                            <td>
                                <img src="<?php echo htmlspecialchars($art['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($art['title']); ?>"
                                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 5px;">
                            </td>
                            
                            <!-- Title -->
                            <td>
                                <strong><?php echo htmlspecialchars($art['title']); ?></strong>
                                <br>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars(substr($art['description'], 0, 50)) . '...'; ?>
                                </small>
                            </td>
                            
                            <!-- Category -->
                            <td>
                                <span class="badge bg-info">
                                    <?php echo htmlspecialchars($art['category']); ?>
                                </span>
                            </td>
                            
                            <!-- Price -->
                            <td>
                                <strong class="text-success">
                                    $<?php echo number_format($art['price'], 2); ?>
                                </strong>
                            </td>
                            
                            <!-- Status -->
                            <td>
                                <?php 
                                $status_class = match($art['status']) {
                                    'active' => 'bg-success',
                                    'sold' => 'bg-warning',
                                    'inactive' => 'bg-secondary',
                                    default => 'bg-secondary'
                                };
                                ?>
                                <span class="badge <?php echo $status_class; ?>">
                                    <?php echo ucfirst($art['status']); ?>
                                </span>
                            </td>
                            
                            <!-- Created Date -->
                            <td>
                                <small><?php echo date('M j, Y', strtotime($art['created_at'])); ?></small>
                            </td>
                            
                            <!-- Actions -->
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="/art-marketplace/gallery/view_single.php?id=<?php echo $art['id']; ?>" 
                                       class="btn btn-outline-primary"
                                       title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    
                                    <button type="button" 
                                            class="btn btn-outline-danger"
                                            onclick="confirmDelete(<?php echo $art['id']; ?>, '<?php echo htmlspecialchars($art['title']); ?>')"
                                            title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
            <p class="mt-3 text-muted">You haven't uploaded any artworks yet.</p>
            <a href="/art-marketplace/artist/upload_art.php" class="btn btn-primary">
                <i class="bi bi-cloud-upload"></i> Upload Your First Artwork
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle"></i> Confirm Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete "<strong id="artworkTitle"></strong>"?</p>
                <p class="text-muted small">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a id="deleteLink" href="#" class="btn btn-danger">
                    <i class="bi bi-trash"></i> Delete Artwork
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, title) {
    document.getElementById('artworkTitle').textContent = title;
    document.getElementById('deleteLink').href = '/art-marketplace/artist/delete_art.php?id=' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

