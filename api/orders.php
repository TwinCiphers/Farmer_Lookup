<?php
require_once __DIR__ . '/config_mysql.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = getPDO();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (isset($_GET['id'])) {
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?'); $stmt->execute([(int)$_GET['id']]); echo json_encode(['success'=>true,'data'=>$stmt->fetch()]);
    } else if (isset($_GET['buyer_id'])) {
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE buyer_id = ?'); $stmt->execute([(int)$_GET['buyer_id']]); echo json_encode(['success'=>true,'data'=>$stmt->fetchAll()]);
    } else { $stmt = $pdo->query('SELECT * FROM orders LIMIT 100'); echo json_encode(['success'=>true,'data'=>$stmt->fetchAll()]); }
    exit();
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $required = ['buyer_id','items']; foreach ($required as $r) { if (empty($data[$r])) { http_response_code(400); echo json_encode(['success'=>false,'message'=>"Missing $r"]); exit(); } }
    // items is array of {product_id, quantity, unit_price}
    $pdo->beginTransaction();
    try {
        $total = 0;
        foreach ($data['items'] as $it) { $total += ($it['unit_price'] * $it['quantity']); }
        $stmt = $pdo->prepare('INSERT INTO orders (buyer_id,total_amount,status,shipping_address,city,state,zip_code,created_at) VALUES (?,?,?,?,?,?,?,NOW())');
        $stmt->execute([$data['buyer_id'],$total,$data['status'] ?? 'pending',$data['shipping_address'] ?? null,$data['city'] ?? null,$data['state'] ?? null,$data['zip_code'] ?? null]);
        $orderId = $pdo->lastInsertId();
        $insertItem = $pdo->prepare('INSERT INTO order_items (order_id,product_id,quantity,unit_price) VALUES (?,?,?,?)');
        foreach ($data['items'] as $it) { $insertItem->execute([$orderId,$it['product_id'],$it['quantity'],$it['unit_price']]); }
        $pdo->commit();
        echo json_encode(['success'=>true,'order_id'=>$orderId]);
    } catch (Exception $e) { $pdo->rollBack(); http_response_code(500); echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
    exit();
}

if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['id'])) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Missing id']); exit(); }
    $fields = [];$params = [];
    $updatable = ['status','shipping_address','city','state','zip_code']; foreach ($updatable as $f) { if (isset($data[$f])) { $fields[] = "$f = ?"; $params[] = $data[$f]; } }
    if (!empty($fields)) { $params[] = (int)$data['id']; $stmt = $pdo->prepare('UPDATE orders SET '.implode(',',$fields).', updated_at = NOW() WHERE id = ?'); $stmt->execute($params); }
    echo json_encode(['success'=>true]); exit();
}

http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']);

?>
