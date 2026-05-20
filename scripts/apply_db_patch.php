<?php
// Apply DB patch: add reset_token and token_expiry if missing
// Usage: php scripts/apply_db_patch.php

require_once __DIR__ . '/../config/db.php';

try {
    $dbName = DB_NAME;

    // Check existing columns
    $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = 'users' AND COLUMN_NAME IN ('reset_token','token_expiry','reset_token_expiry')");
    $stmt->execute(['schema' => $dbName]);
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $existing = array_fill_keys($cols, true);

    // Add reset_token if missing
    if (empty($existing['reset_token'])) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `reset_token` VARCHAR(255) NULL AFTER `password`");
        echo "Added column reset_token\n";
    }

    // If reset_token_expiry missing, try to create it and migrate from token_expiry if present
    if (empty($existing['reset_token_expiry'])) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `reset_token_expiry` DATETIME NULL AFTER `reset_token`");
        echo "Added column reset_token_expiry\n";

        if (!empty($existing['token_expiry'])) {
            // migrate existing values
            $pdo->exec("UPDATE `users` SET reset_token_expiry = token_expiry WHERE token_expiry IS NOT NULL");
            echo "Migrated data from token_expiry to reset_token_expiry\n";
        }
    }

    // Add index on reset_token if not exists (MySQL 8 supports IF NOT EXISTS for index creation)
    try {
        $pdo->exec("CREATE INDEX idx_reset_token ON `users` (`reset_token`)");
    } catch (Exception $ie) {
        // ignore if index exists or not supported
    }

    echo "DB patch completed.\n";
    exit(0);
} catch (Exception $e) {
    echo 'Error applying DB patch: ' . $e->getMessage() . "\n";
    exit(1);
}
