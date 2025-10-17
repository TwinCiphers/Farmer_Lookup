<?php
// Simple MySQL PDO configuration for FarmerLookup
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 0);

$mysqlConfig = [
    // default host for XAMPP/localhost
    'host' => getenv('MYSQL_HOST') ?: '127.0.0.1',
    'port' => getenv('MYSQL_PORT') ?: 3306,
    'database' => getenv('MYSQL_DATABASE') ?: 'farmer',
    'username' => getenv('MYSQL_USER') ?: 'root',
    'password' => getenv('MYSQL_PASSWORD') ?: ''
];

function getPDO() {
    global $mysqlConfig;
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $mysqlConfig['host'], $mysqlConfig['port'], $mysqlConfig['database']);

    try {
        $pdo = new PDO($dsn, $mysqlConfig['username'], $mysqlConfig['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection failed', 'error' => $e->getMessage()]);
        exit();
    }
}

?>
