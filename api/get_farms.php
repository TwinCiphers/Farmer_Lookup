<?php
require_once __DIR__ . '/config_mysql.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getPDO();

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

$stmt = $pdo->prepare('SELECT f.id as farm_id, f.name as farm_name, f.description, f.size_acres, f.established_year, f.farming_methods, u.id as user_id, u.first_name, u.last_name, u.city, u.state FROM farms f JOIN users u ON f.user_id = u.id WHERE u.user_type = ? LIMIT ? OFFSET ?');
$stmt->bindValue(1, 'farmer');
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$farms = $stmt->fetchAll();

echo json_encode(['success' => true, 'data' => $farms]);

?>
