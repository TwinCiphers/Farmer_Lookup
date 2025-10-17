<?php
header('Content-Type: application/json; charset=utf-8');

$result = [
    'mongo' => [
        'available' => false,
        'status' => 'not-tested',
    ],
    'mysql' => [
        'available' => false,
        'status' => 'not-tested',
    ],
    'php_version' => phpversion(),
    'php_extensions' => get_loaded_extensions()
];

// Test MySQL (mysqli)
if (function_exists('mysqli_connect')) {
    $host = getenv('MYSQL_HOST') ?: '127.0.0.1';
    $user = getenv('MYSQL_USER') ?: 'root';
    $pass = getenv('MYSQL_PASSWORD') ?: '';
    $db = getenv('MYSQL_DATABASE') ?: '';
    $port = getenv('MYSQL_PORT') ?: 3306;

    $conn = @mysqli_connect($host, $user, $pass, $db, $port);
    if ($conn) {
        $result['mysql'] = ['available' => true, 'status' => 'ok', 'host' => $host, 'user' => $user, 'database' => $db];
        mysqli_close($conn);
    } else {
        $result['mysql'] = ['available' => true, 'status' => 'error', 'error' => mysqli_connect_error()];
    }
} else {
    $result['mysql'] = ['available' => false, 'status' => 'missing_php_extension', 'note' => 'Enable mysqli in php.ini'];
}

echo json_encode($result, JSON_PRETTY_PRINT);

?>