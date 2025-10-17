<?php
require_once __DIR__ . '/config_mysql.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = getPDO();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (isset($_GET['id'])) {
        $stmt = $pdo->prepare('SELECT * FROM farms WHERE id = ?');
        $stmt->execute([(int)$_GET['id']]);
        echo json_encode(['success'=>true,'data'=>$stmt->fetch()]);
    } else if (isset($_GET['user_id'])) {
        $stmt = $pdo->prepare('SELECT * FROM farms WHERE user_id = ?');
        $stmt->execute([(int)$_GET['user_id']]);
        echo json_encode(['success'=>true,'data'=>$stmt->fetchAll()]);
    } else {
        $stmt = $pdo->query('SELECT * FROM farms LIMIT 100');
        echo json_encode(['success'=>true,'data'=>$stmt->fetchAll()]);
    }
    exit();
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $required = ['user_id','name'];
    foreach ($required as $r) { if (empty($data[$r])) { http_response_code(400); echo json_encode(['success'=>false,'message'=>"Missing $r"]); exit(); } }
    $stmt = $pdo->prepare('INSERT INTO farms (user_id,name,description,size_acres,established_year,farming_methods,created_at) VALUES (?,?,?,?,?,?,NOW())');
    $stmt->execute([$data['user_id'],$data['name'],$data['description'] ?? null,$data['size_acres'] ?? null,$data['established_year'] ?? null,$data['farming_methods'] ?? null]);
    echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId()]);
    exit();
}

if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['id'])) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Missing id']); exit(); }
    $fields = [];$params=[];
    $updatable = ['name','description','size_acres','established_year','farming_methods'];
    foreach ($updatable as $f) { if (isset($data[$f])) { $fields[] = "$f = ?"; $params[] = $data[$f]; } }
    if (!empty($fields)) { $params[] = (int)$data['id']; $stmt = $pdo->prepare('UPDATE farms SET '.implode(',',$fields).', updated_at = NOW() WHERE id = ?'); $stmt->execute($params); }
    echo json_encode(['success'=>true]); exit();
}

if ($method === 'DELETE') {
    if (empty($_GET['id'])) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Missing id']); exit(); }
    $stmt = $pdo->prepare('DELETE FROM farms WHERE id = ?'); $stmt->execute([(int)$_GET['id']]); echo json_encode(['success'=>true]); exit();
}

http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']);

?>
