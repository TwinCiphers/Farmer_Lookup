-- Patch: add missing product columns if they don't exist and recreate products_view
-- Run this file against the 'farmer' database (phpMyAdmin SQL or mysql CLI)

USE `farmer`;

-- Add farming_method if missing
SET @exists = (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = 'farmer' AND TABLE_NAME = 'products' AND COLUMN_NAME = 'farming_method'
);
SET @sql = IF(@exists = 0, 'ALTER TABLE products ADD COLUMN farming_method VARCHAR(100) DEFAULT NULL', 'SELECT "farming_method exists"');
PREPARE stmt FROM @sql; 
EXECUTE stmt; 
DEALLOCATE PREPARE stmt;

-- Add image_url if missing
SET @exists2 = (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = 'farmer' AND TABLE_NAME = 'products' AND COLUMN_NAME = 'image_url'
);
SET @sql2 = IF(@exists2 = 0, 'ALTER TABLE products ADD COLUMN image_url VARCHAR(255) DEFAULT NULL', 'SELECT "image_url exists"');
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- Recreate products_view
DROP VIEW IF EXISTS products_view;
CREATE VIEW products_view AS
SELECT
  p.id,
  p.title AS name,
  p.description,
  p.price AS price_per_unit,
  p.unit AS unit_type,
  p.quantity AS quantity_available,
  COALESCE(f.name, '') AS farm_name,
  -- use farming_method if present, else NULL
  p.farming_method AS growing_method,
  p.is_active,
  p.category_id
FROM products p
LEFT JOIN farms f ON p.farmer_id = f.user_id;

-- Done
SELECT 'PATCH_COMPLETED' AS status;
