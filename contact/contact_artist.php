<?php
/**
 * Contact Artist Page
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/auth_check.php';

$artist_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($artist_id <= 0) {
    header('Location: /art-marketplace/gallery/view_all.php');
    exit();
}

try {
    // Fetch artist information
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND role = "artist"');
    $stmt->execute([$artist_id]);
    
    if ($stmt->rowCount() === 0) {
        header('Location: /art-marketplace/gallery/view_all.php');
        exit();
    }
    
    $artist = $stmt->fetch();
    $page_title = 'Contact ' . htmlspecialchars($artist['name']);
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    header('Location: /art-marketplace/gallery/view_all.php');
    exit();
}

$errors = [];
$success = '';

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sender_name = trim($_POST['sender_name'] ?? '');
    $sender_email = trim($_POST['sender_email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validate inputs
    if (empty($sender_name)) {
        $errors[] = 'Your name is required.';
    }
    
    if (empty($sender_email) || !filter_var($sender_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }
    
    if (empty($message) || strlen($message) < 10) {
        $errors[] = 'Message must be at least 10 characters.';
    }
    
    // Send email if no errors
    if (empty($errors)) {
        try {
            // Prepare email
            $to = htmlspecialchars($artist['email']);
            $subject = 'New Message from ' . htmlspecialchars($sender_name) . ' - Art Marketplace';
            
            $email_body = "
            You have received a new message from an interested buyer!\n
            \n
            From: {$sender_name}
            Email: {$sender_email}
            \n
            Message:
            {$message}
            \n
            ---
            This message was sent through Art Marketplace
            ";
            
            $headers = "From: " . htmlspecialchars($sender_email) . "\r\n";
            $headers .= "Reply-To: " . htmlspecialchars($sender_email) . "\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            
            // Send email
            if (mail($to, $subject, $email_body, $headers)) {
                $success = 'Message sent successfully! The artist will contact you soon.';
                $_POST = [];
            } else {
                $errors[] = 'Failed to send message. Please try again.';
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            $errors[] = 'Error sending message.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Artist Profile Card -->
            <div class="card mb-4 shadow">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <?php if ($artist['profile_image']): ?>
                                <img src="<?php echo htmlspecialchars($artist['profile_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($artist['name']); ?>"
                                     class="rounded-circle"
                                     style="width: 80px; height: 80px; object-fit: cover;">
                            <?php else: ?>
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center"
                                     style="width: 80px; height: 80px;">
                                    <i class="bi bi-person-fill" style="font-size: 2.5rem; color: #ccc;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col">
                            <h4 class="mb-1"><?php echo htmlspecialchars($artist['name']); ?></h4>
                            <p class="text-muted mb-2"><i class="bi bi-palette-fill"></i> Artist</p>
                            
                            <?php if ($artist['bio']): ?>
                                <p class="mb-0"><?php echo htmlspecialchars($artist['bio']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Contact Form Card -->
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-chat-left-text"></i> Send Message</h5>
                </div>
                
                <div class="card-body">
                    <!-- Success Message -->
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Error Messages -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Error:</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Contact Form -->
                    <form method="POST" action="">
                        <!-- Name -->
                        <div class="mb-3">
                            <label for="sender_name" class="form-label">Your Name *</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="sender_name" 
                                name="sender_name"
                                value="<?php echo htmlspecialchars($_POST['sender_name'] ?? ''); ?>"
                                placeholder="Enter your name"
                                required
                            >
                        </div>
                        
                        <!-- Email -->
                        <div class="mb-3">
                            <label for="sender_email" class="form-label">Your Email *</label>
                            <input 
                                type="email" 
                                class="form-control" 
                                id="sender_email" 
                                name="sender_email"
                                value="<?php echo htmlspecialchars($_POST['sender_email'] ?? ''); ?>"
                                placeholder="Enter your email"
                                required
                            >
                            <small class="text-muted">So the artist can reply to you</small>
                        </div>
                        
                        <!-- Message -->
                        <div class="mb-3">
                            <label for="message" class="form-label">Message *</label>
                            <textarea 
                                class="form-control" 
                                id="message" 
                                name="message"
                                rows="5"
                                placeholder="Type your message here (minimum 10 characters)"
                                required
                            ><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                            <small class="text-muted">At least 10 characters</small>
                        </div>
                        
                        <!-- Submit -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-send"></i> Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Alternative Contact Methods -->
            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-telephone"></i> Other Ways to Connect</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Email -->
                        <div class="col-md-6 mb-3">
                            <p class="mb-2"><strong>Email:</strong></p>
                            <p>
                                <a href="mailto:<?php echo htmlspecialchars($artist['email']); ?>">
                                    <?php echo htmlspecialchars($artist['email']); ?>
                                </a>
                            </p>
                        </div>
                        
                        <!-- WhatsApp -->
                        <?php if ($artist['phone']): ?>
                            <div class="col-md-6 mb-3">
                                <p class="mb-2"><strong>WhatsApp:</strong></p>
                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $artist['phone']); ?>" 
                                   target="_blank"
                                   class="btn btn-sm btn-outline-success">
                                    <i class="bi bi-whatsapp"></i> Chat on WhatsApp
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

