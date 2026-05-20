<?php
/**
 * Forgot Password Form
 */
// Ensure consistent timezone
date_default_timezone_set('Asia/Kolkata');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

$page_title = 'Forgot Password';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? 'info';
unset($_SESSION['message'], $_SESSION['message_type']);
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-lg">
                <div class="card-header bg-warning text-white text-center">
                    <h4 class="mb-0"><i class="bi bi-key-fill"></i> Forgot Password</h4>
                </div>

                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="send_reset_email.php">
                        <div class="mb-3">
                            <label for="email" class="form-label">Enter your account email</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="you@example.com" required>
                        </div>

                        <button type="submit" class="btn btn-warning w-100"><i class="bi bi-envelope"></i> Send Reset Link</button>
                    </form>

                    <div class="text-center mt-3">
                        <a href="/art-marketplace/auth/login.php" class="text-decoration-none">Back to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
