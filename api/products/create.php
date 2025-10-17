<?php
require_once __DIR__ . '/../config_mysql.php';

header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$required = ['farmer_id','title','price'];
foreach ($required as $r) {
    if (empty($data[$r])) {
        http_response_code(400);
        echo json_encode(['success'=>false,'message'=>"Missing required field: $r"]);
        exit();
    }
}

$pdo = getPDO();
try {
    $stmt = $pdo->prepare('INSERT INTO products (farmer_id, category_id, title, description, price, unit, quantity, is_active, created_at) VALUES (?,?,?,?,?,?,?,?,NOW())');
    $stmt->execute([
        (int)$data['farmer_id'],
        isset($data['category_id']) ? (int)$data['category_id'] : null,
        $data['title'],
        $data['description'] ?? null,
        (float)$data['price'],
        $data['unit'] ?? 'each',
        isset($data['quantity']) ? (int)$data['quantity'] : 0,
        isset($data['is_active']) ? (int)$data['is_active'] : 1
    ]);

    echo json_encode(['success'=>true,'product_id'=>$pdo->lastInsertId()]);
} catch (Exception $e) {
    http_response_code(500);
    error_log($e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Failed to create product']);
}

?>
