<?php
require_once __DIR__ . '/config_mysql.php';
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
if (empty($input['email']) || empty($input['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email and password required']);
    exit();
}

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT id, password_hash, first_name, last_name, user_type FROM users WHERE email = ?');
$stmt->execute([$input['email']]);
$user = $stmt->fetch();
if (!$user || !password_verify($input['password'], $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    exit();
}

// Minimal session response (no JWT implemented)
echo json_encode(['success' => true, 'user' => [
    'id' => (int)$user['id'],
    'first_name' => $user['first_name'],
    'last_name' => $user['last_name'],
    'user_type' => $user['user_type']
]]);

?>
