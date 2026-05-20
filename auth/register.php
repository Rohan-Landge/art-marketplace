<?php
/**
 * User Registration Page
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

$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'buyer';
    
    // Validate inputs
    if (empty($name)) {
        $errors[] = 'Name is required.';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }
    
    // Strong password validation
    $pwd_errors = [];
    if (empty($password)) {
        $pwd_errors[] = 'Password is required.';
    } else {
        if (strlen($password) < 8) {
            $pwd_errors[] = 'Password must be at least 8 characters.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $pwd_errors[] = 'Password must contain at least one uppercase letter.';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $pwd_errors[] = 'Password must contain at least one lowercase letter.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $pwd_errors[] = 'Password must contain at least one number.';
        }
        if (!preg_match('/[\W_]/', $password)) {
            $pwd_errors[] = 'Password must contain at least one special character.';
        }
    }

    if ($password !== $confirm_password) {
        $pwd_errors[] = 'Passwords do not match.';
    }

    if (!empty($pwd_errors)) {
        $errors = array_merge($errors, $pwd_errors);
    }
    
    if (!in_array($role, ['artist', 'buyer'])) {
        $errors[] = 'Invalid role selected.';
    }
    
    // Check if email already exists
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $errors[] = 'Email already registered. Please login instead.';
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errors[] = 'Database error. Please try again.';
        }
    }
    
    // Register user
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            
            $stmt = $pdo->prepare('
                INSERT INTO users (name, email, password, role)
                VALUES (?, ?, ?, ?)
            ');
            
            $stmt->execute([$name, $email, $hashed_password, $role]);
            
            $success = 'Registration successful! Please login.';
            
            // Clear form
            $_POST = [];
            
            // Redirect after 2 seconds
            header('Refresh: 2; url=/art-marketplace/auth/login.php');
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}

$page_title = 'Register';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white text-center">
                    <h4 class="mb-0"><i class="bi bi-person-plus"></i> Create Account</h4>
                </div>
                
                <div class="card-body">
                    <!-- Success Message -->
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Error Messages -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Registration Error:</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Registration Form -->
                    <form method="POST" action="" class="needs-validation" novalidate>
                        <!-- Name Field -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="name" 
                                name="name"
                                value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                                required
                            >
                            <small class="text-muted">Enter your full name</small>
                        </div>
                        
                        <!-- Email Field -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input 
                                type="email" 
                                class="form-control" 
                                id="email" 
                                name="email"
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                required
                            >
                            <small class="text-muted">We'll never share your email</small>
                        </div>
                        
                        <!-- Role Selection -->
                        <div class="mb-3">
                            <label class="form-label">I am a:</label>
                            <div class="form-check">
                                <input 
                                    class="form-check-input" 
                                    type="radio" 
                                    name="role" 
                                    id="buyer" 
                                    value="buyer"
                                    <?php echo ($_POST['role'] ?? 'buyer') === 'buyer' ? 'checked' : ''; ?>
                                >
                                <label class="form-check-label" for="buyer">
                                    Buyer (Browse & Purchase)
                                </label>
                            </div>
                            <div class="form-check">
                                <input 
                                    class="form-check-input" 
                                    type="radio" 
                                    name="role" 
                                    id="artist" 
                                    value="artist"
                                    <?php echo ($_POST['role'] ?? '') === 'artist' ? 'checked' : ''; ?>
                                >
                                <label class="form-check-label" for="artist">
                                    Artist (Upload & Sell)
                                </label>
                            </div>
                        </div>
                        
                        <!-- Password Field -->
                        <div class="mb-3">
                            <label class="form-label">Password</label>
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

                        <!-- Confirm Password Field -->
                        <div class="mb-3">
                            <label class="form-label">Confirm Password</label>
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
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-circle"></i> Create Account
                        </button>
                    </form>
                    
                    <!-- Login Link -->
                    <div class="text-center mt-3">
                        <p>Already have an account? 
                            <a href="/art-marketplace/auth/login.php" class="text-decoration-none">Login here</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

