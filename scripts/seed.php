<?php
// Seed script for FarmerLookup
// Run from project root with: php scripts\seed.php

require_once __DIR__ . '/../api/config_mysql.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getPDO();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed', 'error' => $e->getMessage()]);
    exit(1);
}

try {
    $pdo->beginTransaction();

    // Insert farmer and buyer (idempotent)
    $password = 'Password123';
    $hash = password_hash($password, PASSWORD_BCRYPT);

    // Farmer
    $q = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $q->execute(['farmer@example.com']);
    $r = $q->fetch();
    if ($r && isset($r['id'])) {
        $farmerId = $r['id'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, first_name, last_name, phone, user_type, business_name, address, city, state, zip_code, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())");
        $stmt->execute([
            'farmer@example.com', $hash, 'Alice', 'Farmer', '555-0101', 'farmer', 'Alice Acres', '123 Farm Rd', 'Smallville', 'State', '12345'
        ]);
        $farmerId = $pdo->lastInsertId();
    }

    // Buyer
    $q->execute(['buyer@example.com']);
    $r2 = $q->fetch();
    if ($r2 && isset($r2['id'])) {
        $buyerId = $r2['id'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, first_name, last_name, phone, user_type, business_name, address, city, state, zip_code, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())");
        $stmt->execute([
            'buyer@example.com', $hash, 'Bob', 'Buyer', '555-0202', 'buyer', null, '456 Market St', 'Bigcity', 'State', '67890'
        ]);
        $buyerId = $pdo->lastInsertId();
    }

    // Insert farm for farmer
    $stmtFarm = $pdo->prepare("INSERT INTO farms (user_id, name, description, size_acres, established_year, farming_methods, created_at) VALUES (?,?,?,?,?,?,NOW())");
    $stmtFarm->execute([$farmerId, 'Alice Acres', 'Family-run mixed vegetable farm', 12.5, 2010, 'organic']);

    // Insert category (if not exists)
    $stmtCat = $pdo->prepare("INSERT INTO categories (name, description, icon) VALUES (?,?,?) ON DUPLICATE KEY UPDATE name = VALUES(name)");
    $stmtCat->execute(['Vegetables Test', 'Sample vegetables', '🥬']);

    $catId = $pdo->lastInsertId();
    if (!$catId) {
        $q = $pdo->prepare('SELECT id FROM categories WHERE name = ? LIMIT 1');
        $q->execute(['Vegetables Test']);
        $r = $q->fetch();
        $catId = $r ? $r['id'] : null;
    }

    // Insert product
    $stmtProd = $pdo->prepare("INSERT INTO products (farmer_id, category_id, title, description, price, unit, quantity, is_active, created_at) VALUES (?,?,?,?,?,?,?,?,NOW())");
    $stmtProd->execute([$farmerId, $catId, 'Tomatoes - 1kg', 'Fresh vine tomatoes', 3.50, 'kg', 100, 1]);
    $productId = $pdo->lastInsertId();

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'farmer_id' => (int)$farmerId,
        'buyer_id' => (int)$buyerId,
        'category_id' => $catId ? (int)$catId : null,
        'product_id' => (int)$productId,
        'note' => 'Passwords for both accounts are \"Password123\". Use login endpoint to test.'
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit(1);
}

?>