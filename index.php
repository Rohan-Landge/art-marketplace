<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Art Marketplace - Home Page
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/auth/auth_check.php';

$page_title = 'Home';

// ✅ Currency (single place control)
$currency = "₹";

// Get featured artworks (latest 6)
try {
    $stmt = $pdo->prepare('
        SELECT a.*, u.name as artist_name 
        FROM artworks a
        JOIN users u ON a.user_id = u.id
        WHERE a.status = "active"
        ORDER BY a.created_at DESC
        LIMIT 6
    ');
    $stmt->execute();
    $featured_artworks = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $featured_artworks = [];
}

// Get stats
try {
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM users WHERE role = "artist"');
    $stmt->execute();
    $artist_count = $stmt->fetch()['count'];
    
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM artworks WHERE status = "active"');
    $stmt->execute();
    $artwork_count = $stmt->fetch()['count'];
    
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM users');
    $stmt->execute();
    $user_count = $stmt->fetch()['count'];
} catch (PDOException $e) {
    error_log($e->getMessage());
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Featured Artworks Section -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-2">Featured Artworks</h2>
            <p class="lead text-muted">Explore the latest additions to our gallery</p>
        </div>
        
        <?php if (count($featured_artworks) > 0): ?>
            <div class="row g-4">
                <?php foreach ($featured_artworks as $artwork): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm artwork-card">

                            <img 
                                src="<?php echo htmlspecialchars($artwork['image']); ?>" 
                                class="card-img-top" 
                                alt="<?php echo htmlspecialchars($artwork['title']); ?>"
                            >

                            <div class="card-body d-flex flex-column">
                                <h5><?php echo htmlspecialchars($artwork['title']); ?></h5>
                                
                                <p class="text-muted small">
                                    By: <?php echo htmlspecialchars($artwork['artist_name']); ?>
                                </p>
                                
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        
                                        <!-- ✅ Currency applied -->
                                        <span class="h5 mb-0 text-success">
                                            <?php echo $currency . number_format($artwork['price'], 2); ?>
                                        </span>

                                    </div>
                                    
                                    <a href="/art-marketplace/gallery/view_single.php?id=<?php echo $artwork['id']; ?>" 
                                       class="btn btn-primary btn-sm w-100">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <div class="text-center py-5">
                <p class="text-muted lead">No artworks available yet.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>