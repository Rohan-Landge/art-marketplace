-- Art Marketplace Professional Upgrade - Database Migration
-- This file contains all new tables and modifications needed for professional features
-- Run after DATABASE_SETUP.sql and DATABASE_PATCH_add_reset_token.sql

USE art_marketplace;

-- ====================================================
-- 1. ALTER USERS TABLE - Add admin support and fields
-- ====================================================

ALTER TABLE users ADD COLUMN IF NOT EXISTS is_admin TINYINT(1) DEFAULT 0 COMMENT 'Admin flag';
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_blocked TINYINT(1) DEFAULT 0 COMMENT 'Account block flag';
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL COMMENT 'Last login timestamp';
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_is_admin (is_admin);
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_is_blocked (is_blocked);

-- ====================================================
-- 2. ALTER ARTWORKS TABLE - Add enhanced fields
-- ====================================================

ALTER TABLE artworks ADD COLUMN IF NOT EXISTS view_count INT DEFAULT 0 COMMENT 'Number of views';
ALTER TABLE artworks ADD COLUMN IF NOT EXISTS favorite_count INT DEFAULT 0 COMMENT 'Number of favorites/wishlists';
ALTER TABLE artworks ADD COLUMN IF NOT EXISTS average_rating DECIMAL(3, 2) DEFAULT 0 COMMENT 'Average rating';
ALTER TABLE artworks ADD COLUMN IF NOT EXISTS review_count INT DEFAULT 0 COMMENT 'Number of reviews';
ALTER TABLE artworks ADD INDEX IF NOT EXISTS idx_view_count (view_count);
ALTER TABLE artworks ADD INDEX IF NOT EXISTS idx_average_rating (average_rating);

-- ====================================================
-- 3. ALTER ORDERS TABLE - Add comprehensive order management
-- ====================================================

ALTER TABLE orders MODIFY COLUMN payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending';
ALTER TABLE orders ADD COLUMN IF NOT EXISTS order_status ENUM('pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending' COMMENT 'Order workflow status';
ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping_address TEXT COMMENT 'Full shipping address';
ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping_city VARCHAR(100) COMMENT 'City for shipping';
ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping_state VARCHAR(100) COMMENT 'State for shipping';
ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping_pincode VARCHAR(10) COMMENT 'Postal code';
ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping_phone VARCHAR(20) COMMENT 'Contact phone for delivery';
ALTER TABLE orders ADD COLUMN IF NOT EXISTS tracking_number VARCHAR(100) COMMENT 'Tracking number for delivery';
ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipped_at TIMESTAMP NULL COMMENT 'When order was shipped';
ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivered_at TIMESTAMP NULL COMMENT 'When order was delivered';
ALTER TABLE orders ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE orders ADD INDEX IF NOT EXISTS idx_order_status (order_status);
ALTER TABLE orders ADD INDEX IF NOT EXISTS idx_payment_status (payment_status);

-- ====================================================
-- 4. WISHLIST TABLE
-- ====================================================

CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    artwork_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (artwork_id) REFERENCES artworks(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_artwork (user_id, artwork_id),
    INDEX idx_user_id (user_id),
    INDEX idx_artwork_id (artwork_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================
-- 5. REVIEWS & RATINGS TABLE
-- ====================================================

CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    artwork_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT,
    helpful_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (artwork_id) REFERENCES artworks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_artwork_review (user_id, artwork_id),
    INDEX idx_artwork_id (artwork_id),
    INDEX idx_user_id (user_id),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================
-- 6. NOTIFICATIONS TABLE
-- ====================================================

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notification_type ENUM(
        'payment_success',
        'artwork_sold',
        'new_order',
        'order_shipped',
        'order_delivered',
        'new_review',
        'new_message',
        'artist_followed',
        'artwork_featured'
    ) NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT,
    related_id INT COMMENT 'Related artwork_id, order_id, etc.',
    is_read TINYINT(1) DEFAULT 0,
    link VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================
-- 7. CATEGORIES TABLE
-- ====================================================

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    slug VARCHAR(100) UNIQUE,
    image VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================
-- 8. ADMIN LOGS TABLE
-- ====================================================

CREATE TABLE IF NOT EXISTS admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    changes TEXT COMMENT 'JSON format changes',
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_admin_id (admin_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================
-- 9. CSRF TOKENS TABLE
-- ====================================================

CREATE TABLE IF NOT EXISTS csrf_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    token VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================
-- 10. DEFAULT CATEGORIES
-- ====================================================

INSERT INTO categories (name, slug, description) VALUES
('Painting', 'painting', 'Traditional and digital paintings'),
('Sculpture', 'sculpture', 'Physical sculptures and 3D art'),
('Photography', 'photography', 'Professional and artistic photography'),
('Digital Art', 'digital-art', 'Digital illustrations and designs'),
('Printmaking', 'printmaking', 'Prints and printmaking artworks'),
('Mixed Media', 'mixed-media', 'Artworks using mixed materials'),
('Crafts', 'crafts', 'Handmade crafts and decorative items'),
('Abstract', 'abstract', 'Abstract and conceptual art')
ON DUPLICATE KEY UPDATE name=name;

-- ====================================================
-- Indexes for performance optimization
-- ====================================================

ALTER TABLE artworks ADD INDEX IF NOT EXISTS idx_category (category);
ALTER TABLE artworks ADD INDEX IF NOT EXISTS idx_price (price);
ALTER TABLE orders ADD INDEX IF NOT EXISTS idx_buyer_id (buyer_id);
ALTER TABLE orders ADD INDEX IF NOT EXISTS idx_artist_id (artist_id);
ALTER TABLE orders ADD INDEX IF NOT EXISTS idx_artwork_id (artwork_id);
ALTER TABLE orders ADD INDEX IF NOT EXISTS idx_created_at (created_at);

-- ====================================================
-- End of Migration
-- ====================================================

