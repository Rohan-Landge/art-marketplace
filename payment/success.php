<?php
require_once __DIR__ . '/config.php';

requireLogin();

$order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$order_id) {
    header('Location: /art-marketplace/index.php');
    exit();
}

try {
    $stmt = $pdo->prepare('SELECT o.*, a.title AS artwork_title, a.image AS artwork_image, u.name AS artist_name FROM orders o JOIN artworks a ON o.artwork_id = a.id JOIN users u ON o.artist_id = u.id WHERE o.id = ? AND o.buyer_id = ?');
    $stmt->execute([$order_id, getCurrentUser()['id']]);
    $order = $stmt->fetch();
    if (!$order) {
        header('Location: /art-marketplace/index.php');
        exit();
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    header('Location: /art-marketplace/index.php');
    exit();
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body text-center">
                    <h3 class="text-success">Payment Successful</h3>
                    <p class="lead">Artwork Purchased Successfully</p>
                    <div class="mb-3">
                        <img src="<?php echo htmlspecialchars($order['artwork_image']); ?>" alt="<?php echo htmlspecialchars($order['artwork_title']); ?>" class="img-fluid" style="max-height:200px; object-fit:cover;">
                    </div>
                    <h5><?php echo htmlspecialchars($order['artwork_title']); ?></h5>
                    <p class="text-muted">Artist: <?php echo htmlspecialchars($order['artist_name']); ?></p>
                    <p>Amount Paid: <strong class="text-success">₹<?php echo number_format($order['amount'], 2); ?></strong></p>
                    <a href="/art-marketplace/gallery/view_all.php" class="btn btn-primary mt-3">Continue Shopping</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
