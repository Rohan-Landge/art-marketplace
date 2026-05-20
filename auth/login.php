<?php
/**
 * User Login Page
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: /art-marketplace/index.php');
    exit();
}

require_once __DIR__ . '/../config/db.php';

$error = '';
$redirect_message = $_SESSION['redirect_message'] ?? '';
unset($_SESSION['redirect_message']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else {
        try {
            // Fetch user from database
            $stmt = $pdo->prepare('SELECT id, name, email, password, role, is_admin, is_blocked FROM users WHERE email = ?');
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() === 0) {
                $error = 'Invalid email or password.';
            } else {
                $user = $stmt->fetch();
                
                // Check if user is blocked
                if ($user['is_blocked']) {
                    $error = 'Your account has been blocked. Please contact support.';
                }
                // Verify password
                elseif (!password_verify($password, $user['password'])) {
                    $error = 'Invalid email or password.';
                } else {
                    // Login successful - set session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['is_admin'] ? 'admin' : $user['role'];
                    
                    // Update last login
                    $update = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
                    $update->execute([$user['id']]);
                    
                    // Redirect based on role
                    if ($user['is_admin']) {
                        header('Location: /art-marketplace/admin/dashboard.php');
                    } elseif ($user['role'] === 'artist') {
                        header('Location: /art-marketplace/artist/dashboard.php');
                    } else {
                        header('Location: /art-marketplace/index.php');
                    }
                    exit();
                }
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Database error. Please try again.';
        }
    }
}

$page_title = 'Login';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-lg">
                <div class="card-header bg-success text-white text-center">
                    <h4 class="mb-0"><i class="bi bi-box-arrow-in-right"></i> Welcome Back</h4>
                </div>
                
                <div class="card-body">
                    <!-- Redirect Message -->
                    <?php if ($redirect_message): ?>
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($redirect_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Error Message -->
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Login Form -->
                    <form method="POST" action="">
                        <!-- Email Field -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input 
                                type="email" 
                                class="form-control" 
                                id="email" 
                                name="email"
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                placeholder="Enter your email"
                                required
                            >
                        </div>
                        
                        <!-- Password Field -->
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <input
                                    type="password"
                                    name="password"
                                    class="form-control password-field"
                                    placeholder="Enter your password"
                                    required
                                >
                                <button
                                    type="button"
                                    class="btn btn-secondary toggle-password"
                                >
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-2 text-end">
                            <a href="/art-marketplace/auth/forgot_password.php" class="text-decoration-none">Forgot Password?</a>
                        </div>
                        
                        <!-- Remember Me (Optional) -->
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">
                                Remember me
                            </label>
                        </div>
                        
                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </button>
                    </form>
                    
                    <!-- Register Link -->
                    <div class="text-center mt-3">
                        <p>Don't have an account? 
                            <a href="/art-marketplace/auth/register.php" class="text-decoration-none">Register here</a>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Test Credentials Info -->
            <div class="alert alert-info mt-3">
                <small>
                    <strong>Test Account:</strong><br>
                    Email: artist@test.com<br>
                    Password: password123
                </small>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

