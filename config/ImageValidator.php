<?php
/**
 * Image Upload Validator and Security Handler
 */

class ImageValidator {
    private $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    private $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
    private $max_size = 5242880; // 5MB in bytes
    private $upload_dir = '/art-marketplace/uploads/artworks/';
    
    /**
     * Validate image file
     */
    public function validate($file) {
        $errors = [];
        
        // Check if file exists
        if (!isset($file) || empty($file['tmp_name'])) {
            $errors[] = 'No file uploaded.';
            return $errors;
        }
        
        // Check file size
        if ($file['size'] > $this->max_size) {
            $errors[] = 'File size exceeds 5MB limit.';
        }
        
        // Check file type by extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowed_extensions)) {
            $errors[] = 'Invalid file type. Only JPG, PNG, and WebP allowed.';
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime, $this->allowed_types)) {
            $errors[] = 'Invalid MIME type. Security validation failed.';
        }
        
        // Additional security: check file content
        if (!$this->is_valid_image($file['tmp_name'])) {
            $errors[] = 'File appears to be corrupted or not a valid image.';
        }
        
        return $errors;
    }
    
    /**
     * Verify file is actually an image
     */
    private function is_valid_image($file_path) {
        $image_info = @getimagesize($file_path);
        return $image_info !== false;
    }
    
    /**
     * Process and save image
     */
    public function save($file, $upload_dir_path) {
        $errors = $this->validate($file);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Generate unique filename
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = uniqid('artwork_') . '.' . $ext;
        $filepath = $upload_dir_path . $filename;
        
        // Ensure directory exists and is writable
        if (!is_dir($upload_dir_path)) {
            mkdir($upload_dir_path, 0755, true);
        }
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => false, 'errors' => ['Failed to save file.']];
        }
        
        // Set proper permissions
        chmod($filepath, 0644);
        
        // Optionally compress image (basic compression)
        $this->compress_image($filepath, $ext);
        
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $this->upload_dir . $filename
        ];
    }
    
    /**
     * Basic image compression
     */
    private function compress_image($file_path, $ext) {
        try {
            if (extension_loaded('gd')) {
                if (in_array($ext, ['jpg', 'jpeg'])) {
                    $image = imagecreatefromjpeg($file_path);
                    if ($image !== false) {
                        imagejpeg($image, $file_path, 85);
                        imagedestroy($image);
                    }
                } elseif ($ext === 'png') {
                    $image = imagecreatefrompng($file_path);
                    if ($image !== false) {
                        imagepng($image, $file_path, 8);
                        imagedestroy($image);
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Image compression error: ' . $e->getMessage());
            // Continue without compression if it fails
        }
    }
    
    /**
     * Delete image file
     */
    public function delete($filename, $upload_dir_path) {
        $filepath = $upload_dir_path . $filename;
        if (file_exists($filepath) && is_file($filepath)) {
            return unlink($filepath);
        }
        return false;
    }
}

?>
