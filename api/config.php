<?php
// MongoDB and Application Configuration
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);

// MongoDB Configuration
$mongoConfig = [
    'host' => $_ENV['MONGO_HOST'] ?? '0.0.0.0',
    'port' => $_ENV['MONGO_PORT'] ?? 27017,
    'database' => $_ENV['MONGO_DATABASE'] ?? 'farmer_lookup',
    'username' => $_ENV['MONGO_USERNAME'] ?? '',
    'password' => $_ENV['MONGO_PASSWORD'] ?? ''
];

// Application Configuration
$appConfig = [
    'jwt_secret' => $_ENV['JWT_SECRET'] ?? 'farmer-lookup-secret-key-change-in-production',
    'upload_path' => dirname(__DIR__) . '/uploads/',
    'max_file_size' => 10 * 1024 * 1024, // 10MB
    'allowed_file_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
    'default_radius' => 25, // miles
    'bcrypt_cost' => 12,
    'session_lifetime' => 3600 * 24 * 30 // 30 days
];

// MongoDB Connection Class
class MongoConnection {
    private static $instance = null;
    private $client;
    private $database;
    
    private function __construct() {
        global $mongoConfig;
        
        try {
            $uri = 'mongodb://';
            if (!empty($mongoConfig['username']) && !empty($mongoConfig['password'])) {
                $uri .= $mongoConfig['username'] . ':' . $mongoConfig['password'] . '@';
            }
            $uri .= $mongoConfig['host'] . ':' . $mongoConfig['port'];
            
            $this->client = new MongoDB\Client($uri);
            $this->database = $this->client->selectDatabase($mongoConfig['database']);
            
            // Test connection
            $this->database->command(['ping' => 1]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database connection failed']);
            exit();
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new MongoConnection();
        }
        return self::$instance;
    }
    
    public function getDatabase() {
        return $this->database;
    }
    
    public function getCollection($name) {
        return $this->database->selectCollection($name);
    }
}

// Utility Functions
function sendResponse($success, $data = [], $message = '', $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => time()
    ]);
    exit();
}

function sendError($message, $statusCode = 400, $details = []) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'details' => $details,
        'timestamp' => time()
    ]);
    exit();
}

function validateRequired($data, $required) {
    $missing = [];
    foreach ($required as $field) {
        if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        sendError('Missing required fields: ' . implode(', ', $missing), 400);
    }
}

function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function hashPassword($password) {
    global $appConfig;
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => $appConfig['bcrypt_cost']]);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function generateId() {
    return new MongoDB\BSON\ObjectId();
}

function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 3959; // miles
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * asin(sqrt($a));
    
    return $earthRadius * $c;
}

function uploadFile($file, $allowedTypes = null) {
    global $appConfig;
    
    if (!$allowedTypes) {
        $allowedTypes = $appConfig['allowed_file_types'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
    }
    
    if ($file['size'] > $appConfig['max_file_size']) {
        return ['success' => false, 'message' => 'File too large'];
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedTypes)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }
    
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $appConfig['upload_path'] . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'message' => 'Failed to save file'];
    }
    
    return [
        'success' => true,
        'filename' => $filename,
        'filepath' => $filepath,
        'url' => 'uploads/' . $filename
    ];
}

// JWT Token Functions (simplified)
function generateToken($payload) {
    global $appConfig;
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload['exp'] = time() + $appConfig['session_lifetime'];
    $payload = json_encode($payload);
    
    $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $appConfig['jwt_secret'], true);
    $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64Header . "." . $base64Payload . "." . $base64Signature;
}

function verifyToken($token) {
    global $appConfig;
    
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }
    
    list($header, $payload, $signature) = $parts;
    
    $validSignature = hash_hmac('sha256', $header . "." . $payload, $appConfig['jwt_secret'], true);
    $validBase64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($validSignature));
    
    if (!hash_equals($signature, $validBase64Signature)) {
        return false;
    }
    
    $decodedPayload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
    
    if (!$decodedPayload || $decodedPayload['exp'] < time()) {
        return false;
    }
    
    return $decodedPayload;
}

function requireAuth() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        sendError('Authentication required', 401);
    }
    
    $token = $matches[1];
    $payload = verifyToken($token);
    
    if (!$payload) {
        sendError('Invalid or expired token', 401);
    }
    
    return $payload;
}

// Initialize MongoDB connection
$mongo = MongoConnection::getInstance();
$db = $mongo->getDatabase();

// Collections
$users = $mongo->getCollection('users');
$products = $mongo->getCollection('products');
$orders = $mongo->getCollection('orders');
$messages = $mongo->getCollection('messages');
$reviews = $mongo->getCollection('reviews');
$categories = $mongo->getCollection('categories');

// Initialize default categories if they don't exist
$categoriesCount = $categories->countDocuments();
if ($categoriesCount === 0) {
    $defaultCategories = [
        ['name' => 'Vegetables', 'description' => 'Fresh vegetables and greens', 'icon' => '🥬'],
        ['name' => 'Fruits', 'description' => 'Fresh seasonal fruits', 'icon' => '🍎'],
        ['name' => 'Herbs', 'description' => 'Fresh herbs and spices', 'icon' => '🌿'],
        ['name' => 'Grains', 'description' => 'Wheat, rice, oats, and other grains', 'icon' => '🌾'],
        ['name' => 'Dairy', 'description' => 'Milk, cheese, and dairy products', 'icon' => '🥛'],
        ['name' => 'Meat', 'description' => 'Fresh meat and poultry', 'icon' => '🥩'],
        ['name' => 'Eggs', 'description' => 'Fresh farm eggs', 'icon' => '🥚'],
        ['name' => 'Honey', 'description' => 'Raw honey and bee products', 'icon' => '🍯']
    ];
    
    foreach ($defaultCategories as $category) {
        $category['_id'] = generateId();
        $category['created_at'] = new MongoDB\BSON\UTCDateTime();
        $categories->insertOne($category);
    }
}
?>