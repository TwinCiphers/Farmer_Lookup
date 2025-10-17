<?php
require_once __DIR__ . '/config_mysql.php';

// Ensure responses are sent as JSON
header('Content-Type: application/json; charset=utf-8');

// Simple CORS support for development/testing. If you serve the site from the
// same origin (recommended), this isn't strictly necessary. Remove or lock
// down the Access-Control-Allow-Origin in production.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit();
}

// Expect JSON
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

function sendJson($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit();
}

$required = ['email', 'password', 'first_name', 'last_name', 'user_type', 'address', 'city', 'state', 'zip_code'];
foreach ($required as $r) {
    if (empty($input[$r])) {
        sendJson(['success' => false, 'message' => "Missing required field: $r"], 400);
    }
}

// Basic email validation
if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    sendJson(['success' => false, 'message' => 'Invalid email address'], 400);
}

// Password length check
if (strlen($input['password']) < 8) {
    sendJson(['success' => false, 'message' => 'Password must be at least 8 characters'], 400);
}

$pdo = getPDO();
try {
    // Check for existing email
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$input['email']]);
    if ($stmt->fetch()) {
        sendJson(['success' => false, 'message' => 'Email already registered'], 409);
    }

    $passwordHash = password_hash($input['password'], PASSWORD_BCRYPT);

    // Insert into users
    $insertUser = $pdo->prepare('INSERT INTO users (email, password_hash, first_name, last_name, phone, user_type, business_name, address, city, state, zip_code, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())');
    $insertUser->execute([
        $input['email'],
        $passwordHash,
        $input['first_name'],
        $input['last_name'],
        $input['phone'] ?? null,
        $input['user_type'],
        $input['business_name'] ?? null,
        $input['address'],
        $input['city'],
        $input['state'],
        $input['zip_code']
    ]);

    $userId = $pdo->lastInsertId();

    // If farmer, add farms row
    if ($input['user_type'] === 'farmer') {
        $insertFarm = $pdo->prepare('INSERT INTO farms (user_id, name, description, size_acres, established_year, farming_methods, created_at) VALUES (?,?,?,?,?,?,NOW())');
        $insertFarm->execute([
            $userId,
            $input['farm_name'] ?? '',
            $input['farm_description'] ?? null,
            $input['farm_size_acres'] ?? null,
            $input['established_year'] ?? null,
            $input['farming_methods'] ?? null
        ]);
    }

    sendJson(['success' => true, 'message' => 'Account created', 'user_id' => (int)$userId], 201);

} catch (Exception $e) {
    // Log unexpected errors to a local file for debugging (do not expose full errors in production)
    $logFile = __DIR__ . '/error.log';
    $errMsg = sprintf("[%s] %s in %s:%d\n", date('c'), $e->getMessage(), $e->getFile(), $e->getLine());
    @file_put_contents($logFile, $errMsg, FILE_APPEND);

    // Return a safe JSON error to client
    sendJson(['success' => false, 'message' => 'Internal server error'], 500);
}

?>
