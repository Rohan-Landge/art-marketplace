<?php
/**
 * Database Configuration and Connection
 * Using PDO for secure database connections
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP password is empty
define('DB_NAME', 'art_marketplace');

try {
    // Create PDO connection
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        )
    );
} catch (PDOException $e) {
    // Log error and display user-friendly message
    error_log($e->getMessage());
    die('Database Connection Error. Please contact administrator.');
}

?>
