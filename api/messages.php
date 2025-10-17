<?php
require_once __DIR__ . '/config_mysql.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = getPDO();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (isset($_GET['conversation']) && isset($_GET['user1']) && isset($_GET['user2'])) {
        $stmt = $pdo->prepare('SELECT * FROM messages WHERE (sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?) ORDER BY created_at ASC');
        $stmt->execute([(int)$_GET['user1'],(int)$_GET['user2'],(int)$_GET['user2'],(int)$_GET['user1']]);
        echo json_encode(['success'=>true,'data'=>$stmt->fetchAll()]);
    } else if (isset($_GET['user_id'])) {
        $stmt = $pdo->prepare('SELECT * FROM messages WHERE sender_id = ? OR recipient_id = ? ORDER BY created_at DESC LIMIT 200'); $stmt->execute([(int)$_GET['user_id'],(int)$_GET['user_id']]); echo json_encode(['success'=>true,'data'=>$stmt->fetchAll()]);
    } else { $stmt = $pdo->query('SELECT * FROM messages ORDER BY created_at DESC LIMIT 200'); echo json_encode(['success'=>true,'data'=>$stmt->fetchAll()]); }
    exit();
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $required = ['sender_id','recipient_id','content']; foreach ($required as $r) { if (empty($data[$r])) { http_response_code(400); echo json_encode(['success'=>false,'message'=>"Missing $r"]); exit(); } }
    $stmt = $pdo->prepare('INSERT INTO messages (sender_id,recipient_id,content,created_at) VALUES (?,?,?,NOW())'); $stmt->execute([$data['sender_id'],$data['recipient_id'],$data['content']]); echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId()]); exit();
}

http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']);

?>
