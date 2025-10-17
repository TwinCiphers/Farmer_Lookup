<?php
require_once __DIR__ . '/config_mysql.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = null;
try {
    $pdo = getPDO();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'PDO connection failed', 'error' => $e->getMessage()]);
    exit();
}

$response = ['success' => true, 'php_version' => phpversion(), 'tables' => []];
$tables = ['users','farms','categories','products','orders','order_items','messages','reviews'];
foreach ($tables as $t) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM `" . $t . "`");
        $row = $stmt->fetch();
        $response['tables'][$t] = (int)$row['cnt'];
    } catch (Exception $e) {
        $response['tables'][$t] = null; // table missing or error
        $response['errors'][$t] = $e->getMessage();
    }
}

echo json_encode($response, JSON_PRETTY_PRINT);

?>