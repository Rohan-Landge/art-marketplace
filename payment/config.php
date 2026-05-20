<?php
/**
 * Payment configuration for Razorpay
 * Fill in your Razorpay Key ID and Secret below.
 */

// Ensure composer autoload exists
$autoloadPath = realpath(__DIR__ . '/../vendor/autoload.php') ?: __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    $projectRoot = realpath(__DIR__ . '/..') ?: __DIR__ . '/..';
    $msg = "Composer autoload not found at: {$autoloadPath}.\n" .
           "Please run the following in your project root ({$projectRoot}):\n\n" .
           "composer require razorpay/razorpay\n";
    error_log($msg);
    // Friendly HTML output for browser
    http_response_code(500);
    echo '<h1>Payment configuration error</h1>';
    echo '<p>Composer dependencies are missing. Run the following in your project root:</p>';
    echo '<pre>composer require razorpay/razorpay</pre>';
    echo '<p>Then reload this page.</p>';
    exit();
}

require_once $autoloadPath;
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/auth_check.php';

// Ensure Razorpay SDK classes are available
if (!class_exists('Razorpay\\Api\\Api')) {
    $msg = 'Razorpay SDK classes not found even though autoload exists. Run: composer require razorpay/razorpay';
    error_log($msg);
    http_response_code(500);
    echo '<h1>Payment SDK error</h1>';
    echo '<p>Razorpay SDK not found. Run:</p>';
    echo '<pre>composer require razorpay/razorpay</pre>';
    exit();
}

use Razorpay\Api\Api;

// Replace these with your Razorpay credentials or set via environment variables
$keyId = getenv('RAZORPAY_KEY_ID') ?: 'rzp_test_SpNBqwrIW2EE2R';
$keySecret = getenv('RAZORPAY_KEY_SECRET') ?: '8TWlWYKmtEif9FqDjvPGJv5W';

// Initialize Razorpay API
try {
    $api = new Api($keyId, $keySecret);
} catch (Throwable $e) {
    error_log('Razorpay init error: ' . $e->getMessage());
    http_response_code(500);
    echo '<h1>Payment gateway initialization error</h1>';
    echo '<p>Could not initialize Razorpay SDK. Check API keys and server configuration.</p>';
    exit();
}

?>
