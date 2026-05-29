<?php
// File: config/db.php
$host = 'localhost';
$dbname = 'topcv_lite'; // Tên database bạn đã tạo ở bước trước
$username = 'root';     // Mặc định của XAMPP/WAMP
$password = '';         // Mặc định thường để trống
define('BASE_URL', 'http://localhost/topcv_lite/');
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Kết nối CSDL thất bại: " . $e->getMessage());
}

// Khởi tạo session luôn ở đây để không phải gọi lại nhiều lần
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>