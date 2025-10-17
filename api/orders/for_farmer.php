<?php
require_once __DIR__ . '/../config_mysql.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = getPDO();
$params = $_GET;

if (empty($params['farmer_id'])) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Missing farmer_id']);
    exit();
}

$farmerId = (int)$params['farmer_id'];

try {
    // Find product ids for this farmer
    $stmt = $pdo->prepare('SELECT id FROM products WHERE farmer_id = ?');
    $stmt->execute([$farmerId]);
    $pids = array_column($stmt->fetchAll(), 'id');

    if (empty($pids)) {
        echo json_encode(['success'=>true,'orders'=>[],'count'=>0]);
        exit();
    }

    // Fetch recent order_items joined with orders and products
    $in = implode(',', array_fill(0, count($pids), '?'));
    $sql = "SELECT oi.order_id, oi.product_id, oi.quantity, oi.unit_price, o.buyer_id, o.status, o.created_at FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.product_id IN ($in) ORDER BY o.created_at DESC LIMIT 50";
    $stmt2 = $pdo->prepare($sql);
    $stmt2->execute($pids);
    $rows = $stmt2->fetchAll();

    echo json_encode(['success'=>true,'orders'=>$rows,'count'=>count($rows)]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
+
?>

