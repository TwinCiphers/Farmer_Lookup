<?php
require_once __DIR__ . '/config_mysql.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = getPDO();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (isset($_GET['product_id'])) { $stmt = $pdo->prepare('SELECT * FROM reviews WHERE product_id = ?'); $stmt->execute([(int)$_GET['product_id']]); echo json_encode(['success'=>true,'data'=>$stmt->fetchAll()]); }
    else if (isset($_GET['reviewed_user_id'])) { $stmt = $pdo->prepare('SELECT * FROM reviews WHERE reviewed_user_id = ?'); $stmt->execute([(int)$_GET['reviewed_user_id']]); echo json_encode(['success'=>true,'data'=>$stmt->fetchAll()]); }
    else { $stmt = $pdo->query('SELECT * FROM reviews LIMIT 200'); echo json_encode(['success'=>true,'data'=>$stmt->fetchAll()]); }
    exit();
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST; $required = ['reviewer_id','rating']; foreach ($required as $r) { if (!isset($data[$r])) { http_response_code(400); echo json_encode(['success'=>false,'message'=>"Missing $r"]); exit(); } }
    $stmt = $pdo->prepare('INSERT INTO reviews (reviewer_id,reviewed_user_id,product_id,rating,comment,created_at) VALUES (?,?,?,?,?,NOW())'); $stmt->execute([$data['reviewer_id'],$data['reviewed_user_id'] ?? null,$data['product_id'] ?? null,$data['rating'],$data['comment'] ?? null]); echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId()]); exit();
}

http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']);

?>
