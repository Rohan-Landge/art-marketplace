<?php
/**
 * Core Helper Functions for Art Marketplace
 * Used across the application for common operations
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is authenticated
 */
function is_authenticated() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Check if user is artist
 */
function is_artist() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'artist';
}

/**
 * Check if user is buyer
 */
function is_buyer() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'buyer';
}

/**
 * Get current user ID
 */
function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Require authentication or redirect
 */
function require_auth() {
    if (!is_authenticated()) {
        $_SESSION['redirect_message'] = 'Please login to continue.';
        header('Location: /art-marketplace/auth/login.php');
        exit();
    }
}

/**
 * Require admin or redirect
 */
function require_admin() {
    require_auth();
    if (!is_admin()) {
        http_response_code(403);
        die('Access Denied. Admin privileges required.');
    }
}

/**
 * Require artist or redirect
 */
function require_artist() {
    require_auth();
    if (!is_artist()) {
        http_response_code(403);
        die('Access Denied. Artist privileges required.');
    }
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    global $pdo;
    
    if (isset($_SESSION['csrf_token'])) {
        return $_SESSION['csrf_token'];
    }
    
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    
    // Store in database
    try {
        $user_id = get_user_id();
        $stmt = $pdo->prepare('INSERT INTO csrf_tokens (user_id, token, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))');
        $stmt->execute([$user_id, $token, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);
    } catch (PDOException $e) {
        error_log($e->getMessage());
    }
    
    return $token;
}

/**
 * Verify CSRF token
 */
/**
 * Verify CSRF token
 *
 * @param string $token CSRF token to verify
 * @return bool
 */
function verify_csrf_token($token) {
    global $pdo;
    
    if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $token) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare('SELECT id FROM csrf_tokens WHERE token = ? AND expires_at > NOW() LIMIT 1');
        $stmt->execute([$token]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * Format currency to INR
 */
/**
 * Format currency to INR
 *
 * @param float|int $amount
 * @return string
 */
function format_currency($amount) {
    return '₹' . number_format((float) $amount, 2);
}

/**
 * Format date in readable format
 */
/**
 * Format date in readable format
 *
 * @param string|null $date
 * @return string
 */
function format_date($date) {
    if (empty($date)) return '-';
    return date('M d, Y', strtotime((string)$date));
}

/**
 * Format datetime with time
 */
/**
 * Format datetime with time
 *
 * @param string|null $datetime
 * @return string
 */
function format_datetime($datetime) {
    if (empty($datetime)) return '-';
    return date('M d, Y h:i A', strtotime((string)$datetime));
}

/**
 * Sanitize input
 */
/**
 * Sanitize input
 *
 * @param string $input
 * @return string
 */
function sanitize($input) {
    return htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8');
}

/**
 * Escape output
 */
/**
 * Escape output
 *
 * @param mixed $output
 * @return string
 */
function escape($output) {
    return htmlspecialchars((string)$output, ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
/**
 * Validate email
 *
 * @param string $email
 * @return bool
 */
function validate_email($email) {
    return filter_var((string)$email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Create notification
 *
 * @param \PDO $pdo
 * @param int $user_id
 * @param string $notification_type
 * @param string $title
 * @param string|null $message
 * @param int|null $related_id
 * @param string|null $link
 * @return bool
 */
function create_notification($pdo, $user_id, $notification_type, $title, $message = '', $related_id = null, $link = '') {
    try {
        $stmt = $pdo->prepare('
            INSERT INTO notifications (user_id, notification_type, title, message, related_id, link)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        return $stmt->execute([$user_id, $notification_type, $title, $message, $related_id, $link]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * Get unread notifications count
 *
 * @param \PDO $pdo
 * @param int $user_id
 * @return int
 */
function get_unread_notifications_count($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0');
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return 0;
    }
}

/**
 * Get recent notifications
 *
 * @param \PDO $pdo
 * @param int $user_id
 * @param int $limit
 * @return array
 */
function get_recent_notifications($pdo, $user_id, $limit = 10) {
    try {
        $stmt = $pdo->prepare('
            SELECT * FROM notifications
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ');
        $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return [];
    }
}

/**
 * Mark notification as read
 *
 * @param \PDO $pdo
 * @param int $notification_id
 * @return bool
 */
function mark_notification_read($pdo, $notification_id) {
    try {
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ?');
        return $stmt->execute([$notification_id]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * Log admin action
 *
 * @param \PDO $pdo
 * @param int $admin_id
 * @param string $action
 * @param string $entity_type
 * @param int|null $entity_id
 * @param mixed|null $changes
 * @return bool
 */
function log_admin_action($pdo, $admin_id, $action, $entity_type, $entity_id = null, $changes = null) {
    try {
        $stmt = $pdo->prepare('
            INSERT INTO admin_logs (admin_id, action, entity_type, entity_id, changes, ip_address)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        return $stmt->execute([
            $admin_id,
            $action,
            $entity_type,
            $entity_id,
            $changes ? json_encode($changes) : null,
            $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * Get user by ID
 *
 * @param \PDO $pdo
 * @param int $user_id
 * @return array|null
 */
function get_user($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return null;
    }
}

/**
 * Get artwork by ID
 *
 * @param \PDO $pdo
 * @param int $artwork_id
 * @return array|null
 */
function get_artwork($pdo, $artwork_id) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM artworks WHERE id = ?');
        $stmt->execute([$artwork_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return null;
    }
}

/**
 * Increment artwork view count
 *
 * @param \PDO $pdo
 * @param int $artwork_id
 * @return bool
 */
function increment_view_count($pdo, $artwork_id) {
    try {
        $stmt = $pdo->prepare('UPDATE artworks SET view_count = view_count + 1 WHERE id = ?');
        return $stmt->execute([$artwork_id]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * Get average rating for artwork
 *
 * @param \PDO $pdo
 * @param int $artwork_id
 * @return array
 */
function get_artwork_rating($pdo, $artwork_id) {
    try {
        $stmt = $pdo->prepare('
            SELECT AVG(rating) as avg_rating, COUNT(*) as review_count
            FROM reviews
            WHERE artwork_id = ?
        ');
        $stmt->execute([$artwork_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return ['avg_rating' => 0, 'review_count' => 0];
    }
}

/**
 * Update artwork rating
 *
 * @param \PDO $pdo
 * @param int $artwork_id
 * @return bool
 */
function update_artwork_rating($pdo, $artwork_id) {
    try {
        $rating_data = get_artwork_rating($pdo, $artwork_id);
        $stmt = $pdo->prepare('
            UPDATE artworks
            SET average_rating = ?, review_count = ?
            WHERE id = ?
        ');
        return $stmt->execute([
            $rating_data['avg_rating'] ?? 0,
            $rating_data['review_count'] ?? 0,
            $artwork_id
        ]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * Check if artwork is in user's wishlist
 *
 * @param \PDO $pdo
 * @param int $user_id
 * @param int $artwork_id
 * @return bool
 */
function is_in_wishlist($pdo, $user_id, $artwork_id) {
    try {
        $stmt = $pdo->prepare('
            SELECT id FROM wishlist
            WHERE user_id = ? AND artwork_id = ?
            LIMIT 1
        ');
        $stmt->execute([$user_id, $artwork_id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * Get user's total earnings
 *
 * @param \PDO $pdo
 * @param int $artist_id
 * @return float|int
 */
function get_artist_earnings($pdo, $artist_id) {
    try {
        $stmt = $pdo->prepare('
            SELECT SUM(amount) as total_earnings
            FROM orders
            WHERE artist_id = ? AND payment_status = "paid"
        ');
        $stmt->execute([$artist_id]);
        $result = $stmt->fetch();
        return $result['total_earnings'] ?? 0;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return 0;
    }
}

/**
 * Get artist's total artworks sold
 *
 * @param \PDO $pdo
 * @param int $artist_id
 * @return int
 */
function get_artist_sales_count($pdo, $artist_id) {
    try {
        $stmt = $pdo->prepare('
            SELECT COUNT(*) as sales_count
            FROM orders
            WHERE artist_id = ? AND payment_status = "paid"
        ');
        $stmt->execute([$artist_id]);
        $result = $stmt->fetch();
        return $result['sales_count'] ?? 0;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return 0;
    }
}

/**
 * Get latest orders for artist
 *
 * @param \PDO $pdo
 * @param int $artist_id
 * @param int $limit
 * @return array
 */
function get_artist_recent_orders($pdo, $artist_id, $limit = 10) {
    try {
        $stmt = $pdo->prepare('
            SELECT o.*, u.name as buyer_name, a.title as artwork_title
            FROM orders o
            JOIN users u ON o.buyer_id = u.id
            JOIN artworks a ON o.artwork_id = a.id
            WHERE o.artist_id = ?
            ORDER BY o.created_at DESC
            LIMIT ?
        ');
        $stmt->bindParam(1, $artist_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return [];
    }
}

/**
 * Generate unique filename
 *
 * @param string $original_filename
 * @return string
 */
function generate_unique_filename($original_filename) {
    $ext = strtolower(pathinfo((string)$original_filename, PATHINFO_EXTENSION));
    return uniqid('artwork_') . '.' . $ext;
}

?>
