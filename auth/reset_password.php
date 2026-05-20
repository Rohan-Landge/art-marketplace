<?php
/**
 * Reset Password Page
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure consistent timezone for token validation
date_default_timezone_set('Asia/Kolkata');

require_once __DIR__ . '/../config/db.php';

$error = '';
$success = '';

// Get token from GET or POST
$token = $_GET['token'] ?? $_POST['token'] ?? '';
$token = trim($token);

if (empty($token)) {
    $error = 'Invalid or missing token.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($token)) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Server-side validation for password strength
    $pwd_errors = [];
    if (empty($password)) {
        $pwd_errors[] = 'Password is required.';
    } else {
        if (strlen($password) < 8) $pwd_errors[] = 'Password must be at least 8 characters.';
        if (!preg_match('/[A-Z]/', $password)) $pwd_errors[] = 'Password must contain at least one uppercase letter.';
        if (!preg_match('/[a-z]/', $password)) $pwd_errors[] = 'Password must contain at least one lowercase letter.';
        if (!preg_match('/[0-9]/', $password)) $pwd_errors[] = 'Password must contain at least one number.';
        if (!preg_match('/[\W_]/', $password)) $pwd_errors[] = 'Password must contain at least one special character.';
    }
    if ($password !== $confirm_password) $pwd_errors[] = 'Passwords do not match.';

    if (!empty($pwd_errors)) {
        $error = implode(' ', $pwd_errors);
    } else {
        try {
            // Validate token and expiry (use reset_token_expiry)
            $stmt = $pdo->prepare('SELECT * FROM users WHERE reset_token = ? AND reset_token_expiry > NOW() LIMIT 1');
            $stmt->execute([$token]);
            $user = $stmt->fetch();

            if (!$user) {
                // For debugging, check if token exists but expired
                $diag = $pdo->prepare('SELECT id, reset_token_expiry FROM users WHERE reset_token = ? LIMIT 1');
                $diag->execute([$token]);
                $diagRow = $diag->fetch();
                if ($diagRow) {
                    $error = 'Invalid or expired token. Token existed and expired at: ' . ($diagRow['reset_token_expiry'] ?? 'unknown');
                } else {
                    $error = 'Invalid or expired token. No matching token found.';
                }
            } else {
                // Update password and clear token (use reset_token_expiry)
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $update = $pdo->prepare('UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?');
                $update->execute([$hashed, $user['id']]);

                // Redirect to login with success message
                $_SESSION['redirect_message'] = 'Password has been reset successfully. Please login.';
                header('Location: /art-marketplace/auth/login.php');
                exit();
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Database error. Please try again later.';
        }
    }
}

$page_title = 'Reset Password';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white text-center">
                    <h4 class="mb-0"><i class="bi bi-key"></i> Reset Password</h4>
                </div>

                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($error) || $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                        <form method="POST" action="">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <div class="input-group">
                                    <input
                                        type="password"
                                        name="password"
                                        class="form-control password-field"
                                        required
                                    >
                                    <button
                                        type="button"
                                        class="btn btn-secondary toggle-password"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Minimum 8 chars, include uppercase, lowercase, number, special</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <div class="input-group">
                                    <input
                                        type="password"
                                        name="confirm_password"
                                        class="form-control password-field"
                                        required
                                    >
                                    <button
                                        type="button"
                                        class="btn btn-secondary toggle-password"
                                    >
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Set New Password</button>
                        </form>
                    <?php endif; ?>

                    <div class="text-center mt-3">
                        <a href="/art-marketplace/auth/login.php" class="text-decoration-none">Back to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
