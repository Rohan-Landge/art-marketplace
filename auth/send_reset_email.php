<?php
/**
 * Handle sending password reset email
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Show development errors temporarily
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure consistent timezone for token expiry
date_default_timezone_set('Asia/Kolkata');


require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/mail_config.php';

// Composer autoload (PHPMailer may not be installed)
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
} else {
    error_log('Composer autoload not found: ' . $composerAutoload);
    $_SESSION['message'] = 'Email service not configured. Please contact the site administrator.';
    $_SESSION['message_type'] = 'danger';
    header('Location: forgot_password.php');
    exit();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

$email = trim($_POST['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['message'] = 'Please provide a valid email address.';
    $_SESSION['message_type'] = 'danger';
    header('Location: forgot_password.php');
    exit();
}

try {
    // Check if user exists
    $stmt = $pdo->prepare('SELECT id, name FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Save token and expiry (use reset_token_expiry column)
        $update = $pdo->prepare('UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?');
        $update->execute([$token, $expiry, $user['id']]);

        // Build reset link
        $reset_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/art-marketplace/auth/reset_password.php?token=' . urlencode($token);

        // Send email via PHPMailer when available, otherwise fallback to mail()
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                // Debug output to error_log for development
                $mail->SMTPDebug = (defined('MAIL_DEBUG') && MAIL_DEBUG) ? 2 : 0; // change to 0 in production
                $mail->Debugoutput = function($str, $level) { error_log('PHPMailer: ' . trim($str)); };

                $mail->Host = MAIL_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = MAIL_USERNAME;
                $mail->Password = MAIL_PASSWORD;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = MAIL_PORT;

                $mail->setFrom(MAIL_USERNAME, 'Art Marketplace');
                $mail->addAddress($email, $user['name']);

                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $mail->Body = '<p>Hi ' . htmlspecialchars($user['name']) . ',</p>' .
                    '<p>We received a request to reset your password. Click the link below to set a new password. This link will expire in 1 hour.</p>' .
                    '<p><a href="' . $reset_link . '">Reset Password</a></p>' .
                    '<p>If you did not request this, please ignore this email.</p>';

                $mail->send();
            } catch (PHPMailerException $e) {
                // Log but do not disclose details to user
                error_log('Password reset email failed (PHPMailer): ' . $e->getMessage());
            }
        } else {
            // Fallback to PHP mail() if PHPMailer is not installed
            $subject = 'Password Reset Request';
            $headers = 'From: ' . MAIL_USERNAME . "\r\n" .
                       'Reply-To: ' . MAIL_USERNAME . "\r\n" .
                       'Content-Type: text/html; charset=UTF-8' . "\r\n";
            $body = '<p>Hi ' . htmlspecialchars($user['name']) . ',</p>' .
                    '<p>We received a request to reset your password. Click the link below to set a new password. This link will expire in 1 hour.</p>' .
                    '<p><a href="' . $reset_link . '">Reset Password</a></p>' .
                    '<p>If you did not request this, please ignore this email.</p>';

            if (!mail($email, $subject, $body, $headers)) {
                error_log('mail() failed to send password reset to: ' . $email);
            }
        }
    }

    // Always show a neutral success message to avoid revealing registered emails
    $_SESSION['message'] = 'If an account with that email exists, a password reset link has been sent.';
    $_SESSION['message_type'] = 'success';
    header('Location: forgot_password.php');
    exit();

} catch (\Exception $e) {
    error_log('FORGOT PASSWORD ERROR: ' . $e->getMessage());
    // Show the real error temporarily for debugging (remove in production)
    $_SESSION['message'] = $e->getMessage();
    $_SESSION['message_type'] = 'danger';
    header('Location: forgot_password.php');
    exit();
}
