<?php
require_once __DIR__ . '/config_mysql.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = getPDO();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // list users or get single by id
    if (isset($_GET['id'])) {
        $stmt = $pdo->prepare('SELECT id, email, first_name, last_name, phone, user_type, business_name, address, city, state, zip_code FROM users WHERE id = ?');
        $stmt->execute([(int)$_GET['id']]);
        $user = $stmt->fetch();
        echo json_encode(['success' => true, 'data' => $user]);
    } else {
        $stmt = $pdo->query('SELECT id, email, first_name, last_name, user_type FROM users LIMIT 100');
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    }
    exit();
}

if ($method === 'PUT' || $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    if ($method === 'POST') {
        // create user (admin / internal)
        $required = ['email', 'password', 'first_name', 'last_name', 'user_type'];
        foreach ($required as $r) {
            if (empty($data[$r])) { http_response_code(400); echo json_encode(['success'=>false,'message'=>"Missing $r"]); exit(); }
        }
        $hash = password_hash($data['password'], PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('INSERT INTO users (email,password_hash,first_name,last_name,user_type,created_at) VALUES (?,?,?,?,?,NOW())');
        $stmt->execute([$data['email'],$hash,$data['first_name'],$data['last_name'],$data['user_type']]);
        echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId()]);
        exit();
    } else {
        // update user
        if (empty($data['id'])) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Missing id']); exit(); }
        $fields = [];
        $params = [];
        $updatable = ['first_name','last_name','phone','business_name','address','city','state','zip_code'];
        foreach ($updatable as $f) {
            if (isset($data[$f])) { $fields[] = "$f = ?"; $params[] = $data[$f]; }
        }
        if (!empty($fields)) {
            $params[] = (int)$data['id'];
            $stmt = $pdo->prepare('UPDATE users SET ' . implode(',', $fields) . ', updated_at = NOW() WHERE id = ?');
            $stmt->execute($params);
        }
        echo json_encode(['success'=>true]);
        exit();
    }
}

if ($method === 'DELETE') {
    if (empty($_GET['id'])) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Missing id']); exit(); }
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([(int)$_GET['id']]);
    echo json_encode(['success'=>true]);
    exit();
}

http_response_code(405);
echo json_encode(['success'=>false,'message'=>'Method not allowed']);

?>
