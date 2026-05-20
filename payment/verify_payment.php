<?php
/**
 * Payment Verification and Order Creation
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../config/helpers.php';

// Only POST accepted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /art-marketplace/index.php');
    exit();
}

// Ensure logged in
require_auth();

$razorpay_payment_id = sanitize($_POST['razorpay_payment_id'] ?? '');
$razorpay_order_id = sanitize($_POST['razorpay_order_id'] ?? '');
$razorpay_signature = sanitize($_POST['razorpay_signature'] ?? '');
$artwork_id = isset($_POST['artwork_id']) ? (int) $_POST['artwork_id'] : 0;

if (!$razorpay_payment_id || !$razorpay_order_id || !$razorpay_signature || !$artwork_id) {
    header('Location: /art-marketplace/payment/failed.php');
    exit();
}

try {
    // Verify signature
    $attributes = [
        'razorpay_order_id' => $razorpay_order_id,
        'razorpay_payment_id' => $razorpay_payment_id,
        'razorpay_signature' => $razorpay_signature
    ];

    $api->utility->verifyPaymentSignature($attributes);

    // Prevent race conditions and duplicate purchases
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT * FROM artworks WHERE id = ? FOR UPDATE');
    $stmt->execute([$artwork_id]);
    $artwork = $stmt->fetch();

    if (!$artwork) {
        $pdo->rollBack();
        header('Location: /art-marketplace/payment/failed.php');
        exit();
    }

    if (isset($artwork['status']) && $artwork['status'] === 'sold') {
        $pdo->rollBack();
        header('Location: /art-marketplace/payment/failed.php?error=already_sold');
        exit();
    }

    // Mark artwork as sold
    $update = $pdo->prepare('UPDATE artworks SET status = ? WHERE id = ?');
    $update->execute(['sold', $artwork_id]);

    // Insert order record with full details
    $buyer_id = get_user_id();
    $artist_id = $artwork['user_id'];
    $amount = $artwork['price'];

    $ins = $pdo->prepare('
        INSERT INTO orders (
            artwork_id, buyer_id, artist_id, payment_id, razorpay_order_id, 
            amount, payment_status, order_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $ins->execute([
        $artwork_id, 
        $buyer_id, 
        $artist_id, 
        $razorpay_payment_id, 
        $razorpay_order_id, 
        $amount, 
        'paid',
        'paid'
    ]);

    $order_id = $pdo->lastInsertId();

    // Create notifications
    // Notify buyer
    create_notification(
        $pdo, 
        $buyer_id, 
        'payment_success', 
        'Purchase Successful', 
        'Your payment for "' . escape($artwork['title']) . '" has been completed successfully.',
        $order_id,
        '/art-marketplace/orders/my_orders.php'
    );

    // Notify artist
    $buyer = get_user($pdo, $buyer_id);
    create_notification(
        $pdo, 
        $artist_id, 
        'artwork_sold', 
        'Artwork Sold!', 
        'Your artwork "' . escape($artwork['title']) . '" has been sold to ' . escape($buyer['name'] ?? 'A buyer') . ' for ' . format_currency($amount),
        $order_id,
        '/art-marketplace/orders/artist_orders.php'
    );

    $pdo->commit();

    header('Location: /art-marketplace/payment/success.php?id=' . $order_id);
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Payment verification error: ' . $e->getMessage());
    header('Location: /art-marketplace/payment/failed.php');
    exit();
}

?>
