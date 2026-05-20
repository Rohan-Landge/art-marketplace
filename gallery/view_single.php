<?php
/**
 * Gallery - View Single Artwork
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

// Get artwork ID
$artwork_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($artwork_id <= 0) {
    header('Location: /art-marketplace/gallery/view_all.php');
    exit();
}

try {
    // Fetch artwork with artist details and rating
    $stmt = $pdo->prepare('
        SELECT a.*, u.name as artist_name, u.email as artist_email, u.phone as artist_phone,
               COALESCE(a.average_rating, 0) as rating,
               COALESCE(a.review_count, 0) as review_count
        FROM artworks a
        JOIN users u ON a.user_id = u.id
        WHERE a.id = ?
    ');
    $stmt->execute([$artwork_id]);
    
    if ($stmt->rowCount() === 0) {
        header('Location: /art-marketplace/gallery/view_all.php');
        exit();
    }
    
    $artwork = $stmt->fetch();
    $page_title = escape($artwork['title']);
    
    // Increment view count
    $view_stmt = $pdo->prepare('UPDATE artworks SET view_count = view_count + 1 WHERE id = ?');
    $view_stmt->execute([$artwork_id]);
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    header('Location: /art-marketplace/gallery/view_all.php');
    exit();
}

// Check if user purchased this artwork (for review eligibility)
$user_id = is_authenticated() ? get_user_id() : null;
$user_purchased = false;
$user_review = null;
$is_wishlisted = false;

if ($user_id) {
    try {
        $stmt = $pdo->prepare('
            SELECT id FROM orders 
            WHERE artwork_id = ? AND user_id = ? AND payment_status = "paid"
            LIMIT 1
        ');
        $stmt->execute([$artwork_id, $user_id]);
        $user_purchased = $stmt->rowCount() > 0;
        
        // Check if user has already reviewed
        if ($user_purchased) {
            $stmt = $pdo->prepare('
                SELECT * FROM reviews 
                WHERE artwork_id = ? AND user_id = ?
                LIMIT 1
            ');
            $stmt->execute([$artwork_id, $user_id]);
            $user_review = $stmt->fetch();
        }
        
        // Check if in wishlist
        $is_wishlisted = is_in_wishlist($pdo, $user_id, $artwork_id);
        
    } catch (PDOException $e) {
        error_log($e->getMessage());
    }
}

// Fetch related artworks
try {
    $stmt = $pdo->prepare('
        SELECT a.*, u.name as artist_name
        FROM artworks a
        JOIN users u ON a.user_id = u.id
        WHERE a.status = "active" AND a.user_id = ? AND a.id != ?
        LIMIT 3
    ');
    $stmt->execute([$artwork['user_id'], $artwork_id]);
    $related_artworks = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $related_artworks = [];
}

// Fetch reviews
try {
    $stmt = $pdo->prepare('
        SELECT r.*, u.name as reviewer_name
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.artwork_id = ?
        ORDER BY r.created_at DESC
        LIMIT 10
    ');
    $stmt->execute([$artwork_id]);
    $reviews = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $reviews = [];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container my-5">
    <!-- Artwork Details -->
    <div class="row mb-5">
        <!-- Image Column -->
        <div class="col-md-6">
            <div class="artwork-display position-relative">
                <img 
                    src="<?php echo escape($artwork['image']); ?>" 
                    alt="<?php echo escape($artwork['title']); ?>"
                    class="img-fluid rounded shadow"
                    style="max-height: 500px; object-fit: cover; width: 100%;"
                >
                
                <!-- Wishlist Button -->
                <?php if (is_authenticated() && !is_admin()): ?>
                <button class="btn btn-light position-absolute top-0 end-0 m-3 rounded-circle wishlist-toggle" 
                        data-artwork-id="<?php echo $artwork_id; ?>"
                        title="Add to wishlist">
                    <i class="bi <?php echo $is_wishlisted ? 'bi-heart-fill text-danger' : 'bi-heart'; ?>" style="font-size: 1.5rem;"></i>
                </button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Details Column -->
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body">
                    <!-- Title -->
                    <h2 class="card-title mb-2">
                        <?php echo escape($artwork['title']); ?>
                    </h2>
                    
                    <!-- Rating -->
                    <div class="mb-3">
                        <?php if ($artwork['review_count'] > 0): ?>
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-warning">
                                    <?php for ($i = 0; $i < 5; $i++): ?>
                                        <i class="bi <?php echo $i < round($artwork['rating']) ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                                    <?php endfor; ?>
                                </span>
                                <span class="text-muted"><?php echo round($artwork['rating'], 1); ?>/5</span>
                                <span class="badge bg-secondary"><?php echo $artwork['review_count']; ?> reviews</span>
                            </div>
                        <?php else: ?>
                            <span class="text-muted small">No reviews yet</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Artist Info -->
                    <p class="text-muted mb-3">
                        <i class="bi bi-person-fill"></i> 
                        By <strong><?php echo escape($artwork['artist_name']); ?></strong>
                    </p>
                    
                    <!-- Category -->
                    <?php if ($artwork['category']): ?>
                        <p class="mb-3">
                            <span class="badge bg-info">
                                <?php echo escape($artwork['category']); ?>
                            </span>
                        </p>
                    <?php endif; ?>
                    
                    <!-- Stats -->
                    <div class="row mb-3">
                        <div class="col-6">
                            <small class="text-muted">Views</small>
                            <p class="mb-0"><strong><?php echo $artwork['view_count']; ?></strong></p>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Favorites</small>
                            <p class="mb-0"><strong><?php echo $artwork['favorite_count']; ?></strong></p>
                        </div>
                    </div>
                    
                    <!-- Price -->
                    <div class="mb-4 p-3 bg-light rounded">
                        <p class="text-muted small mb-1">Price</p>
                        <h3 class="text-success mb-0">
                            <?php echo format_currency($artwork['price']); ?>
                        </h3>
                    </div>
                    
                    <!-- Description -->
                    <div class="mb-4">
                        <h5 class="mb-2">Description</h5>
                        <p><?php echo nl2br(escape($artwork['description'])); ?></p>
                    </div>
                    
                    <!-- Created Date -->
                    <p class="text-muted small">
                        <i class="bi bi-calendar"></i> 
                        Created: <?php echo format_date($artwork['created_at']); ?>
                    </p>
                    
                    <!-- Action Buttons -->
                    <hr>
                    
                    <div class="d-grid gap-2">
                        <!-- Buy Now / Sold Out Button -->
                        <?php if (isset($artwork['status']) && $artwork['status'] === 'sold'): ?>
                            <button class="btn btn-secondary btn-lg" disabled>
                                <i class="bi bi-x-circle"></i> Sold Out
                            </button>
                        <?php else: ?>
                            <a href="/art-marketplace/payment/create_order.php?id=<?php echo $artwork['id']; ?>" 
                               class="btn btn-primary btn-lg">
                                <i class="bi bi-bag"></i> Buy Now
                            </a>
                        <?php endif; ?>

                        <!-- Contact Artist Button -->
                        <a href="/art-marketplace/contact/contact_artist.php?id=<?php echo $artwork['user_id']; ?>" 
                           class="btn btn-success btn-lg">
                            <i class="bi bi-chat-left-text"></i> Contact Artist
                        </a>
                        
                        <!-- WhatsApp Link -->
                        <?php if ($artwork['artist_phone']): ?>
                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $artwork['artist_phone']); ?>?text=Hi%20I%20am%20interested%20in%20your%20artwork%20-%20<?php echo urlencode($artwork['title']); ?>" 
                               target="_blank"
                               class="btn btn-outline-success">
                                <i class="bi bi-whatsapp"></i> WhatsApp
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Reviews Section -->
    <div class="row mb-5">
        <div class="col-md-8 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-chat-left-quote"></i> Reviews & Ratings
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Add Review Form (only if user purchased) -->
                    <?php if (is_authenticated() && $user_purchased && !$user_review): ?>
                        <div class="mb-4 pb-4 border-bottom">
                            <h5 class="mb-3">Leave a Review</h5>
                            <form id="reviewForm" method="POST" action="/art-marketplace/ajax/submit_review.php">
                                <input type="hidden" name="artwork_id" value="<?php echo $artwork_id; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                
                                <!-- Rating Stars -->
                                <div class="mb-3">
                                    <label class="form-label">Rating</label>
                                    <div id="ratingStars" class="mb-2">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" hidden>
                                            <label for="star<?php echo $i; ?>" class="star-label" style="font-size: 2rem; cursor: pointer; color: #ddd;">
                                                <i class="bi bi-star-fill"></i>
                                            </label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                
                                <!-- Review Text -->
                                <div class="mb-3">
                                    <label class="form-label">Your Review</label>
                                    <textarea class="form-control" name="review_text" rows="4" placeholder="Share your thoughts about this artwork..." required></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Submit Review
                                </button>
                            </form>
                        </div>
                    <?php elseif (is_authenticated() && $user_review): ?>
                        <div class="mb-4 pb-4 border-bottom alert alert-info">
                            <p class="mb-0">
                                <i class="bi bi-check-circle"></i> You've already reviewed this artwork
                            </p>
                        </div>
                    <?php elseif (is_authenticated() && !$user_purchased): ?>
                        <div class="mb-4 pb-4 border-bottom alert alert-warning">
                            <p class="mb-0">
                                <i class="bi bi-info-circle"></i> Purchase this artwork to leave a review
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="mb-4 pb-4 border-bottom alert alert-warning">
                            <p class="mb-0">
                                <i class="bi bi-info-circle"></i> 
                                <a href="/art-marketplace/auth/login.php">Login</a> to leave a review
                            </p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Reviews List -->
                    <h5 class="mb-3">Reviews (<?php echo count($reviews); ?>)</h5>
                    
                    <?php if (empty($reviews)): ?>
                        <p class="text-muted text-center py-4">No reviews yet. Be the first to review!</p>
                    <?php else: ?>
                        <div class="review-list">
                            <?php foreach ($reviews as $review): ?>
                                <div class="mb-3 pb-3 border-bottom">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <strong><?php echo escape($review['reviewer_name']); ?></strong>
                                            <div class="text-warning small">
                                                <?php for ($i = 0; $i < 5; $i++): ?>
                                                    <i class="bi <?php echo $i < $review['rating'] ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <small class="text-muted"><?php echo format_datetime($review['created_at']); ?></small>
                                    </div>
                                    <p class="mb-0"><?php echo escape($review['review_text']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Related Artworks -->
    <?php if (count($related_artworks) > 0): ?>
        <div class="row mt-5">
            <div class="col-12">
                <h3 class="mb-4">More from this Artist</h3>
            </div>
            
            <?php foreach ($related_artworks as $related): ?>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        <img 
                            src="<?php echo escape($related['image']); ?>" 
                            class="card-img-top"
                            style="height: 250px; object-fit: cover;"
                            alt="<?php echo escape($related['title']); ?>"
                        >
                        <div class="card-body">
                            <h5 class="card-title text-truncate">
                                <?php echo escape($related['title']); ?>
                            </h5>
                            <p class="text-success h5">
                                <?php echo format_currency($related['price']); ?>
                            </p>
                            <a href="/art-marketplace/gallery/view_single.php?id=<?php echo $related['id']; ?>" 
                               class="btn btn-primary btn-sm w-100">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.star-label {
    transition: color 0.2s;
}

.star-label:hover,
.star-label:hover ~ .star-label,
input[type="radio"]:checked ~ .star-label {
    color: #ffc107;
}

.wishlist-toggle {
    transition: transform 0.2s;
}

.wishlist-toggle:hover {
    transform: scale(1.1);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Wishlist toggle
    const wishlistBtn = document.querySelector('.wishlist-toggle');
    if (wishlistBtn) {
        wishlistBtn.addEventListener('click', function() {
            const artworkId = this.dataset.artworkId;
            fetch('/art-marketplace/ajax/toggle_wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'artwork_id=' + artworkId
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const icon = wishlistBtn.querySelector('i');
                    if (data.wishlisted) {
                        icon.classList.remove('bi-heart');
                        icon.classList.add('bi-heart-fill', 'text-danger');
                        showToast('Added to wishlist');
                    } else {
                        icon.classList.remove('bi-heart-fill', 'text-danger');
                        icon.classList.add('bi-heart');
                        showToast('Removed from wishlist');
                    }
                }
            })
            .catch(err => {
                console.error('Error:', err);
                showToast('Error updating wishlist', 'error');
            });
        });
    }
    
    // Review form AJAX
    const reviewForm = document.getElementById('reviewForm');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('/art-marketplace/ajax/submit_review.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('Review submitted successfully');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(data.message || 'Error submitting review', 'error');
                }
            })
            .catch(err => {
                console.error('Error:', err);
                showToast('Error submitting review', 'error');
            });
        });
    }
});

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'error' ? 'danger' : 'success'} position-fixed bottom-0 end-0 m-3`;
    toast.style.zIndex = '9999';
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
                </div>
            </div>
        </div>
    </div>
    
