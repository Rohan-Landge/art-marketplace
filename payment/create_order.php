<?php
// Quick check for composer autoload to provide clearer diagnostics early
$autoloadPath = realpath(__DIR__ . '/../vendor/autoload.php') ?: __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    http_response_code(500);
    echo '<h1>Payment dependency missing</h1>';
    echo '<p>Composer autoload not found. Run in project root:</p>';
    echo '<pre>composer require razorpay/razorpay</pre>';
    exit();
}

require_once __DIR__ . '/config.php';

// Ensure user is logged in
requireLogin();

// Validate artwork id
$artwork_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$artwork_id || $artwork_id <= 0) {
    header('Location: /art-marketplace/gallery/view_all.php');
    exit();
}

try {
    // Fetch artwork
    $stmt = $pdo->prepare('SELECT a.*, u.name as artist_name, u.email as artist_email FROM artworks a JOIN users u ON a.user_id = u.id WHERE a.id = ?');
    $stmt->execute([$artwork_id]);
    $artwork = $stmt->fetch();

    if (!$artwork) {
        header('Location: /art-marketplace/gallery/view_all.php');
        exit();
    }

    if (isset($artwork['status']) && $artwork['status'] === 'sold') {
        header('Location: /art-marketplace/gallery/view_single.php?id=' . $artwork_id . '&error=sold');
        exit();
    }

    $amountPaise = (int) round($artwork['price'] * 100);
    if ($amountPaise <= 0) {
        header('Location: /art-marketplace/gallery/view_single.php?id=' . $artwork_id . '&error=invalid_price');
        exit();
    }

    // Create Razorpay order
    $orderData = [
        'receipt' => 'rcpt_' . time() . '_' . $artwork_id,
        'amount' => $amountPaise,
        'currency' => 'INR',
        'payment_capture' => 1
    ];

    $razorpayOrder = $api->order->create($orderData);
    $razorpayOrderId = $razorpayOrder['id'];

} catch (Exception $e) {
    error_log($e->getMessage());
    header('Location: /art-marketplace/gallery/view_single.php?id=' . $artwork_id . '&error=payment_init');
    exit();
}

$user = getCurrentUser();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body text-center">
                    <h4 class="card-title">Redirecting to payment</h4>
                    <p class="text-muted">You will be redirected to Razorpay checkout. Please do not refresh the page.</p>
                    <p class="mb-0"><strong><?php echo htmlspecialchars($artwork['title']); ?></strong></p>
                    <p class="text-success h3">₹<?php echo number_format($artwork['price'], 2); ?></p>
                    <div class="spinner-border text-success mt-3" role="status"><span class="visually-hidden">Loading...</span></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    (function(){
        var options = {
            key: <?php echo json_encode($keyId); ?>,
            amount: <?php echo json_encode($amountPaise); ?>,
            currency: 'INR',
            name: <?php echo json_encode($artwork['title']); ?>,
            description: 'Purchase of artwork',
            image: <?php echo json_encode($artwork['image'] ?? ''); ?>,
            order_id: <?php echo json_encode($razorpayOrderId); ?>,
            handler: function (response){
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = '/art-marketplace/payment/verify_payment.php';
                var fields = {
                    razorpay_payment_id: response.razorpay_payment_id,
                    razorpay_order_id: response.razorpay_order_id,
                    razorpay_signature: response.razorpay_signature,
                    artwork_id: <?php echo json_encode($artwork_id); ?>
                };
                for (var k in fields){
                    var inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.name = k;
                    inp.value = fields[k];
                    form.appendChild(inp);
                }
                document.body.appendChild(form);
                form.submit();
            },
            prefill: {
                name: <?php echo json_encode($user['name'] ?? ''); ?>,
                email: <?php echo json_encode($user['email'] ?? ''); ?>,
                contact: <?php
                    // Try to fetch phone from users table
                    try {
                        $phoneStmt = $pdo->prepare('SELECT phone FROM users WHERE id = ?');
                        $phoneStmt->execute([$user['id']]);
                        $phoneRow = $phoneStmt->fetch();
                        echo json_encode($phoneRow['phone'] ?? '');
                    } catch (Exception $e) {
                        echo json_encode('');
                    }
                ?>
            },
            theme: {color: '#0d6efd'}
        };

        var rzp = new Razorpay(options);
        rzp.open();
    })();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
