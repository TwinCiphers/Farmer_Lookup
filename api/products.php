<?php
require_once __DIR__ . '/config_mysql.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = getPDO();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (isset($_GET['id'])) {
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?'); $stmt->execute([(int)$_GET['id']]); echo json_encode(['success'=>true,'data'=>$stmt->fetch()]);
    } else if (isset($_GET['farmer_id'])) {
        $stmt = $pdo->prepare('SELECT * FROM products WHERE farmer_id = ?'); $stmt->execute([(int)$_GET['farmer_id']]); echo json_encode(['success'=>true,'data'=>$stmt->fetchAll()]);
    } else {
        $stmt = $pdo->query('SELECT * FROM products LIMIT 100'); echo json_encode(['success'=>true,'data'=>$stmt->fetchAll()]);
    }
    exit();
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $required = ['farmer_id','title','price']; foreach ($required as $r) { if (empty($data[$r])) { http_response_code(400); echo json_encode(['success'=>false,'message'=>"Missing $r"]); exit(); } }
        $stmt = $pdo->prepare('INSERT INTO products (farmer_id,category_id,title,description,price,unit,quantity,image_url,farming_method,is_active,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())');
        $stmt->execute([
            $data['farmer_id'],
            $data['category_id'] ?? null,
            $data['title'],
            $data['description'] ?? null,
            $data['price'],
            $data['unit'] ?? 'each',
            $data['quantity'] ?? 0,
            $data['image_url'] ?? null,
            $data['farming_method'] ?? null,
            $data['is_active'] ?? 1
        ]);
    echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId()]); exit();
}

if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true); if (empty($data['id'])) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Missing id']); exit(); }
    $fields = [];$params=[]; $updatable = ['category_id','title','description','price','unit','quantity','is_active']; foreach ($updatable as $f) { if (isset($data[$f])) { $fields[] = "$f = ?"; $params[] = $data[$f]; } }
        $updatable = ['category_id','title','description','price','unit','quantity','image_url','farming_method','is_active']; foreach ($updatable as $f) { if (isset($data[$f])) { $fields[] = "$f = ?"; $params[] = $data[$f]; } }
    if (!empty($fields)) { $params[] = (int)$data['id']; $stmt = $pdo->prepare('UPDATE products SET '.implode(',',$fields).', updated_at = NOW() WHERE id = ?'); $stmt->execute($params); }
    echo json_encode(['success'=>true]); exit();
}

if ($method === 'DELETE') { if (empty($_GET['id'])) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Missing id']); exit(); } $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?'); $stmt->execute([(int)$_GET['id']]); echo json_encode(['success'=>true]); exit(); }

http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']);

?>
