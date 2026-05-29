<?php
// File: config/db.php — kết nối PDO; override bằng config/db.local.php (không commit)

$defaults = [
    'host'     => 'localhost',
    'dbname'   => 'topcv_lite',
    'username' => 'root',
    'password' => '',
    'base_url' => 'http://localhost/topcv_lite/',
];

$localFile = __DIR__ . '/db.local.php';
if (is_file($localFile)) {
    $local = require $localFile;
    if (!is_array($local)) {
        throw new RuntimeException('config/db.local.php phải return array.');
    }
    $cfg = array_merge($defaults, $local);
} else {
    $cfg = $defaults;
}

$host     = $cfg['host'];
$dbname   = $cfg['dbname'];
$username = $cfg['username'];
$password = $cfg['password'];

if (!defined('BASE_URL')) {
    define('BASE_URL', rtrim($cfg['base_url'], '/') . '/');
}

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Kết nối CSDL thất bại: ' . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
