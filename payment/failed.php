<?php
require_once __DIR__ . '/config.php';
requireLogin();

$artwork_id = filter_input(INPUT_GET, 'artwork_id', FILTER_VALIDATE_INT);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body text-center">
                    <h3 class="text-danger">Payment Failed</h3>
                    <p class="lead">There was an issue processing your payment.</p>
                    <p class="text-muted">You can retry the purchase or contact the artist.</p>
                    <div class="d-flex justify-content-center gap-2 mt-3">
                        <?php if ($artwork_id): ?>
                            <a href="/art-marketplace/payment/create_order.php?id=<?php echo $artwork_id; ?>" class="btn btn-primary">Retry</a>
                        <?php endif; ?>
                        <a href="/art-marketplace/gallery/view_all.php" class="btn btn-secondary">Continue Browsing</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
