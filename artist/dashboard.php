<?php
/**
 * Artist Dashboard
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/auth_check.php';

// Require artist authentication
requireArtist();

$user_id = getCurrentUserId();
$page_title = 'Artist Dashboard';

// Get artist statistics
try {
    // Total artworks
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM artworks WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $artwork_count = $stmt->fetch()['count'];
    
    // Total revenue (if status is "sold")
    $stmt = $pdo->prepare('SELECT SUM(price) as total FROM artworks WHERE user_id = ? AND status = "sold"');
    $stmt->execute([$user_id]);
    $total_revenue = $stmt->fetch()['total'] ?? 0;
    
    // Active artworks
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM artworks WHERE user_id = ? AND status = "active"');
    $stmt->execute([$user_id]);
    $active_count = $stmt->fetch()['count'];
    
} catch (PDOException $e) {
    error_log($e->getMessage());
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$current_user = getCurrentUser();
?>

<div class="container my-5">
    <!-- Welcome Section -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="bg-gradient p-5 rounded text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h1 class="display-5 mb-2">Welcome, <?php echo htmlspecialchars($current_user['name']); ?>!</h1>
                <p class="lead mb-0">Manage your artworks and track your sales</p>
            </div>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row g-4 mb-5">
        <!-- Total Artworks -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted small mb-1">Total Artworks</p>
                            <h3 class="mb-0"><?php echo $artwork_count; ?></h3>
                        </div>
                        <i class="bi bi-collection" style="font-size: 2.5rem; color: #667eea;"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Active Artworks -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted small mb-1">Active Artworks</p>
                            <h3 class="mb-0"><?php echo $active_count; ?></h3>
                        </div>
                        <i class="bi bi-check-circle" style="font-size: 2.5rem; color: #48bb78;"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Total Revenue -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted small mb-1">Total Revenue</p>
                            <h3 class="mb-0">$<?php echo number_format($total_revenue, 2); ?></h3>
                        </div>
                        <i class="bi bi-currency-dollar" style="font-size: 2.5rem; color: #f6ad55;"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sold Artworks -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted small mb-1">Quick Actions</p>
                            <a href="/art-marketplace/artist/upload_art.php" class="btn btn-primary btn-sm">
                                Upload Art
                            </a>
                        </div>
                        <i class="bi bi-cloud-upload" style="font-size: 2.5rem; color: #ed8936;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row mb-5">
        <div class="col-12">
            <h4 class="mb-3">Quick Actions</h4>
            <div class="d-grid gap-2 d-sm-flex">
                <a href="/art-marketplace/artist/upload_art.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-cloud-upload"></i> Upload New Artwork
                </a>
                <a href="/art-marketplace/artist/my_art.php" class="btn btn-outline-primary btn-lg">
                    <i class="bi bi-collection"></i> View My Artworks
                </a>
                <a href="/art-marketplace/gallery/view_all.php" class="btn btn-outline-secondary btn-lg">
                    <i class="bi bi-images"></i> Browse Gallery
                </a>
            </div>
        </div>
    </div>
    
    <!-- Help Section -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Tips for Success</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-2">✓ Upload high-quality images of your artworks</li>
                        <li class="mb-2">✓ Write detailed descriptions to attract buyers</li>
                        <li class="mb-2">✓ Set competitive pricing for your art</li>
                        <li class="mb-2">✓ Keep your profile and contact info updated</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-question-circle"></i> Need Help?</h5>
                </div>
                <div class="card-body">
                    <p>For support or questions:</p>
                    <p class="mb-0">
                        Email: <a href="mailto:support@artmarketplace.com">support@artmarketplace.com</a>
                    </p>
                    <p class="mb-0">
                        WhatsApp: <a href="https://wa.me/1234567890">+1 (234) 567-890</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

