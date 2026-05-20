<?php
/**
 * Authentication Check Helper
 * Verifies if user is logged in and redirects accordingly
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_email']);
}

/**
 * Check if user is an artist
 * @return bool
 */
function isArtist() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'artist';
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user info
 * @return array|null
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['user_role']
    ];
}

/**
 * Redirect to login if not authenticated
 * @param string $message Optional message to display
 */
function requireLogin($message = '') {
    if (!isLoggedIn()) {
        $_SESSION['redirect_message'] = $message ?: 'Please login to continue.';
        header('Location: /art-marketplace/auth/login.php');
        exit();
    }
}

/**
 * Redirect to login if not an artist
 * @param string $message Optional message to display
 */
function requireArtist($message = '') {
    if (!isArtist()) {
        $_SESSION['error_message'] = $message ?: 'You must be an artist to access this page.';
        header('Location: /art-marketplace/index.php');
        exit();
    }
}

/**
 * Logout user
 */
function logout() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_destroy();
    header('Location: /art-marketplace/index.php');
    exit();
}

?>
