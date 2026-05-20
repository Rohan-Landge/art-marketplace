<?php
/**
 * Upload Artwork Page
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/auth_check.php';

// Require artist authentication
requireArtist();

$user_id = getCurrentUserId();
$page_title = 'Upload Artwork';

$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $category = trim($_POST['category'] ?? '');
    
    // Validate inputs
    if (empty($title)) {
        $errors[] = 'Title is required.';
    }
    
    if (empty($description) || strlen($description) < 20) {
        $errors[] = 'Description must be at least 20 characters.';
    }
    
    if (empty($price) || !is_numeric($price) || $price <= 0) {
        $errors[] = 'Valid price is required.';
    }
    
    if (empty($category)) {
        $errors[] = 'Category is required.';
    }
    
    // Handle file upload
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Image file is required.';
    } else {
        $file = $_FILES['image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        // Validate file type
        if (!in_array($file['type'], $allowed_types)) {
            $errors[] = 'Only JPG, PNG, GIF, and WebP images are allowed.';
        }
        
        // Validate file size
        if ($file['size'] > $max_size) {
            $errors[] = 'File size must not exceed 5MB.';
        }
    }
    
    // Process upload if no errors
    if (empty($errors) && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        try {
            // Create unique filename
            $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('artwork_', true) . '.' . $file_ext;
            $upload_dir = __DIR__ . '/../uploads/artworks/';
            $file_path = $upload_dir . $filename;
            
            // Ensure upload directory exists
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                // Store relative path
                $image_path = '/art-marketplace/uploads/artworks/' . $filename;
                
                // Insert into database
                $stmt = $pdo->prepare('
                    INSERT INTO artworks (user_id, title, description, price, category, image)
                    VALUES (?, ?, ?, ?, ?, ?)
                ');
                
                $stmt->execute([
                    $user_id,
                    $title,
                    $description,
                    $price,
                    $category,
                    $image_path
                ]);
                
                $success = 'Artwork uploaded successfully!';
                $_POST = [];
                
                // Redirect after 2 seconds
                header('Refresh: 2; url=/art-marketplace/artist/my_art.php');
            } else {
                $errors[] = 'Failed to upload image. Please try again.';
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errors[] = 'Database error. Please try again.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="bi bi-cloud-upload"></i> Upload Artwork</h4>
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
                            <strong>Validation Errors:</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Upload Form -->
                    <form method="POST" action="" enctype="multipart/form-data">
                        <!-- Title -->
                        <div class="mb-3">
                            <label for="title" class="form-label">Artwork Title *</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="title" 
                                name="title"
                                value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                                placeholder="Enter artwork title"
                                required
                            >
                            <small class="text-muted">Give your artwork a catchy title</small>
                        </div>
                        
                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea 
                                class="form-control" 
                                id="description" 
                                name="description"
                                rows="5"
                                placeholder="Describe your artwork in detail (minimum 20 characters)"
                                required
                            ><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            <small class="text-muted">At least 20 characters. Include details about style, inspiration, materials, etc.</small>
                        </div>
                        
                        <!-- Category -->
                        <div class="mb-3">
                            <label for="category" class="form-label">Category *</label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="">Select a category</option>
                                <option value="Painting" <?php echo ($_POST['category'] ?? '') === 'Painting' ? 'selected' : ''; ?>>Painting</option>
                                <option value="Digital Art" <?php echo ($_POST['category'] ?? '') === 'Digital Art' ? 'selected' : ''; ?>>Digital Art</option>
                                <option value="Sculpture" <?php echo ($_POST['category'] ?? '') === 'Sculpture' ? 'selected' : ''; ?>>Sculpture</option>
                                <option value="Photography" <?php echo ($_POST['category'] ?? '') === 'Photography' ? 'selected' : ''; ?>>Photography</option>
                                <option value="Drawing" <?php echo ($_POST['category'] ?? '') === 'Drawing' ? 'selected' : ''; ?>>Drawing</option>
                                <option value="Printmaking" <?php echo ($_POST['category'] ?? '') === 'Printmaking' ? 'selected' : ''; ?>>Printmaking</option>
                                <option value="Other" <?php echo ($_POST['category'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <!-- Price -->
                        <div class="mb-3">
                            <label for="price" class="form-label">Price (INR)
                            <div class="input-group">
                               <span class="input-group-text">₹</span>
                                <input 
                                    type="number" 
                                    class="form-control" 
                                    id="price" 
                                    name="price"
                                    step="0.01"
                                    value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>"
                                    placeholder="0.00"
                                    required
                                >
                            </div>
                            <small class="text-muted">Set a price for your artwork</small>
                        </div>
                        
                        <!-- Image Upload -->
                        <div class="mb-3">
                            <label for="image" class="form-label">Artwork Image *</label>
                            <div class="input-group">
                                <input 
                                    type="file" 
                                    class="form-control" 
                                    id="image" 
                                    name="image"
                                    accept="image/*"
                                    required
                                >
                            </div>
                            <small class="text-muted">Supported formats: JPG, PNG, GIF, WebP. Max size: 5MB</small>
                            
                            <!-- Image Preview -->
                            <div id="imagePreview" class="mt-3">
                                <img id="previewImage" src="" alt="Preview" style="display:none; max-width: 200px; height: auto;">
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-cloud-upload"></i> Upload Artwork
                            </button>
                            <a href="/art-marketplace/artist/dashboard.php" class="btn btn-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Image Preview Script -->
<script>
document.getElementById('image').addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.getElementById('previewImage');
            img.src = e.target.result;
            img.style.display = 'block';
        }
        reader.readAsDataURL(file);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

