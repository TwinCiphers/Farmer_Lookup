-- Farmer Lookup consolidated MySQL schema (cleaned)
-- Generated: 2025-10-17
-- Purpose: create database and tables needed by the webapp and APIs

CREATE DATABASE IF NOT EXISTS `farmer` CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
USE `farmer`;

-- Users: buyers and farmers
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(100) DEFAULT NULL,
  `last_name` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `user_type` ENUM('buyer','farmer') NOT NULL DEFAULT 'buyer',
  `business_name` VARCHAR(255) DEFAULT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `state` VARCHAR(100) DEFAULT NULL,
  `zip_code` VARCHAR(20) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_email` (`email`),
  KEY `idx_user_type` (`user_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Farms: profile information for farmers (one-to-one with users.id when a farmer)
CREATE TABLE IF NOT EXISTS `farms` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `size_acres` DECIMAL(8,2) DEFAULT NULL,
  `established_year` SMALLINT DEFAULT NULL,
  `farming_methods` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_farms_user_id` (`user_id`),
  CONSTRAINT `fk_farms_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories for products
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `icon` VARCHAR(8) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_categories_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Products listed by farmers
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `farmer_id` INT UNSIGNED NOT NULL,
  `category_id` INT UNSIGNED DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `unit` VARCHAR(50) DEFAULT 'each',
  `quantity` INT DEFAULT 0,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `farming_method` VARCHAR(100) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_products_farmer` (`farmer_id`),
  KEY `idx_products_category` (`category_id`),
  CONSTRAINT `fk_products_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders table
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `buyer_id` INT UNSIGNED DEFAULT NULL,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('pending','confirmed','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `shipping_address` VARCHAR(255) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `state` VARCHAR(100) DEFAULT NULL,
  `zip_code` VARCHAR(20) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_orders_buyer` (`buyer_id`),
  CONSTRAINT `fk_orders_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order items
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order` (`order_id`),
  KEY `idx_order_items_product` (`product_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Messages between users
CREATE TABLE IF NOT EXISTS `messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sender_id` INT UNSIGNED NOT NULL,
  `recipient_id` INT UNSIGNED NOT NULL,
  `content` TEXT NOT NULL,
  `read_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_messages_sender` (`sender_id`),
  KEY `idx_messages_recipient` (`recipient_id`),
  CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_messages_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reviews
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reviewer_id` INT UNSIGNED NOT NULL,
  `reviewed_user_id` INT UNSIGNED DEFAULT NULL,
  `product_id` INT UNSIGNED DEFAULT NULL,
  `rating` TINYINT UNSIGNED NOT NULL,
  `comment` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reviews_reviewer` (`reviewer_id`),
  KEY `idx_reviews_reviewed_user` (`reviewed_user_id`),
  KEY `idx_reviews_product` (`product_id`),
  CONSTRAINT `fk_reviews_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_reviewed_user` FOREIGN KEY (`reviewed_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product view used by frontend to map friendly field names
-- Ensure product columns exist (safe when importing into an existing DB)
-- Add farming_method if missing
SET @exists = (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'farming_method'
);
SET @sql = IF(@exists = 0, 'ALTER TABLE products ADD COLUMN farming_method VARCHAR(100) DEFAULT NULL', 'SELECT "farming_method exists"');
PREPARE stmt FROM @sql; 
EXECUTE stmt; 
DEALLOCATE PREPARE stmt;

-- Add image_url if missing
SET @exists2 = (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'image_url'
);
SET @sql2 = IF(@exists2 = 0, 'ALTER TABLE products ADD COLUMN image_url VARCHAR(255) DEFAULT NULL', 'SELECT "image_url exists"');
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

DROP VIEW IF EXISTS `products_view`;
CREATE VIEW `products_view` AS
SELECT
  p.id,
  p.title AS name,
  p.description,
  p.price AS price_per_unit,
  p.unit AS unit_type,
  p.quantity AS quantity_available,
  COALESCE(f.name, '') AS farm_name,
  p.farming_method AS growing_method,
  p.is_active,
  p.category_id
FROM products p
LEFT JOIN farms f ON p.farmer_id = f.user_id;

-- Default categories
INSERT INTO `categories` (`name`, `description`, `icon`) VALUES
  ('Vegetables', 'Fresh vegetables and greens', '🥬'),
  ('Fruits', 'Fresh seasonal fruits', '🍎'),
  ('Herbs', 'Fresh herbs and spices', '🌿'),
  ('Grains', 'Wheat, rice, oats, and other grains', '🌾'),
  ('Dairy', 'Milk, cheese, and dairy products', '🥛'),
  ('Meat', 'Fresh meat and poultry', '🥩'),
  ('Eggs', 'Fresh farm eggs', '🥚'),
  ('Honey', 'Raw honey and bee products', '🍯')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Notes:
-- 1) Import this file in phpMyAdmin (http://localhost/phpmyadmin) or using the mysql CLI.
-- 2) XAMPP MySQL default user: root and by default no password. Adjust credentials when connecting.
-- 3) You may want to add sample users (use password_hash in PHP to generate bcrypt hashes) before testing login.

-- --------------------------------------------------------------------
-- Sample seed data (optional) - edit before importing. Passwords must be bcrypt hashes
-- To generate a bcrypt hash from PHP CLI:
-- php -r "echo password_hash('Password123', PASSWORD_BCRYPT) . PHP_EOL;"
-- Copy the generated hash into the INSERT below (replace <BCRYPT_HASH>)
-- --------------------------------------------------------------------

-- Example users (one farmer, one buyer)
-- INSERT INTO `users` (email, password_hash, first_name, last_name, phone, user_type, business_name, address, city, state, zip_code, created_at) VALUES
-- ('farmer@example.com', '<BCRYPT_HASH>', 'Alice', 'Farmer', '555-0101', 'farmer', 'Alice Acres', '123 Farm Rd', 'Smallville', 'State', '12345', NOW()),
-- ('buyer@example.com',  '<BCRYPT_HASH>', 'Bob',   'Buyer',  '555-0202', 'buyer',  NULL,          '456 Market St', 'Bigcity',  'State', '67890', NOW());

-- After inserting the farmer above, create a farm entry for that farmer (replace FARMER_USER_ID with the inserted id)
-- INSERT INTO `farms` (user_id, name, description, size_acres, established_year, farming_methods, created_at) VALUES
-- (FARMER_USER_ID, 'Alice Acres', 'Family-run mixed vegetable farm', 12.5, 2010, 'organic', NOW());

-- Example category and product (replace FARMER_USER_ID and CATEGORY_ID as needed)
-- INSERT INTO `categories` (name, description, icon) VALUES ('Test Vegetables', 'Sample category', '🥬') ON DUPLICATE KEY UPDATE name = VALUES(name);
-- SET @cid = (SELECT id FROM categories WHERE name = 'Test Vegetables' LIMIT 1);
-- INSERT INTO `products` (farmer_id, category_id, title, description, price, unit, quantity, is_active, created_at) VALUES
-- (FARMER_USER_ID, @cid, 'Tomatoes - 1kg', 'Fresh vine tomatoes', 3.50, 'kg', 100, 1, NOW());

-- Helpful: to find the inserted user id after importing, run:
-- SELECT id,email,first_name,last_name FROM users;

