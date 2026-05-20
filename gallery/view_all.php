<?php
/**
 * Gallery - View All Artworks
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/auth_check.php';

$page_title = 'Gallery';

// Get filtering options
$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$sort = $_GET['sort'] ?? 'latest';

// Build query
$query = 'SELECT a.*, u.name as artist_name FROM artworks a 
          JOIN users u ON a.user_id = u.id 
          WHERE a.status = "active"';

$params = [];

// Apply search filter
if (!empty($search)) {
    $query .= ' AND (a.title LIKE ? OR a.description LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Apply category filter
if (!empty($category)) {
    $query .= ' AND a.category = ?';
    $params[] = $category;
}

// Apply sorting
switch ($sort) {
    case 'price_low':
        $query .= ' ORDER BY a.price ASC';
        break;
    case 'price_high':
        $query .= ' ORDER BY a.price DESC';
        break;
    case 'oldest':
        $query .= ' ORDER BY a.created_at ASC';
        break;
    case 'latest':
    default:
        $query .= ' ORDER BY a.created_at DESC';
        break;
}

$query .= ' LIMIT 12';

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $artworks = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $artworks = [];
}

// Get categories for filter
try {
    $stmt = $pdo->prepare('SELECT DISTINCT category FROM artworks WHERE category IS NOT NULL AND status = "active" ORDER BY category');
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $categories = [];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container my-5">
    <!-- Page Title -->
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold">Art Gallery</h1>
        <p class="lead text-muted">Discover unique artworks from talented artists</p>
    </div>
    
    <!-- Filters and Search -->
    <div class="row mb-4">
        <div class="col-md-12">
            <form method="GET" action="" class="row g-2">
                <!-- Search -->
                <div class="col-md-4">
                    <input 
                        type="text" 
                        class="form-control" 
                        name="search" 
                        placeholder="Search artworks..."
                        value="<?php echo htmlspecialchars($search); ?>"
                    >
                </div>
                
                <!-- Category Filter -->
                <div class="col-md-3">
                    <select class="form-select" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['category']); ?>" 
                                <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['category']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Sort -->
                <div class="col-md-3">
                    <select class="form-select" name="sort">
                        <option value="latest" <?php echo $sort === 'latest' ? 'selected' : ''; ?>>Latest</option>
                        <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest</option>
                        <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                    </select>
                </div>
                
                <!-- Search Button -->
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Results Count -->
    <div class="mb-3">
        <p class="text-muted">Showing <?php echo count($artworks); ?> artwork(s)</p>
    </div>
    
    <!-- Gallery Grid -->
    <?php if (count($artworks) > 0): ?>
        <div class="row g-4">
            <?php foreach ($artworks as $artwork): ?>
                <div class="col-md-4 col-lg-3">
                    <div class="card h-100 shadow-sm hover-shadow artwork-card">
                        <!-- Image -->
                        <div class="artwork-image-container">
                            <img 
                                src="<?php echo htmlspecialchars($artwork['image']); ?>" 
                                class="card-img-top artwork-image" 
                                alt="<?php echo htmlspecialchars($artwork['title']); ?>"
                            >
                        </div>
                        
                        <!-- Card Body -->
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-truncate">
                                <?php echo htmlspecialchars($artwork['title']); ?>
                            </h5>
                            
                            <p class="card-text text-muted small text-truncate">
                                By: <?php echo htmlspecialchars($artwork['artist_name']); ?>
                            </p>
                            
                            <p class="text-muted small">
                                <?php echo htmlspecialchars($artwork['category']); ?>
                            </p>
                            
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="h5 mb-0 text-success">
                                        $<?php echo number_format($artwork['price'], 2); ?>
                                    </span>
                                </div>
                                
                                <a href="/art-marketplace/gallery/view_single.php?id=<?php echo $artwork['id']; ?>" 
                                   class="btn btn-primary btn-sm w-100">
                                    <i class="bi bi-eye"></i> View Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
            <p class="mt-3 text-muted">No artworks found. Try adjusting your filters.</p>
            <a href="/art-marketplace/gallery/view_all.php" class="btn btn-primary">Clear Filters</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

