<?php
// File: employer/auth_check.php

// 1. Khởi tạo session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Kiểm tra đăng nhập và vai trò
// Nếu chưa đăng nhập HOẶC không phải là 'employer' -> Đuổi về trang login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employer') {
    header("Location: ../login.php");
    exit();
}

// 3. Kết nối CSDL để kiểm tra trạng thái duyệt (Real-time)
// Dùng __DIR__ để đảm bảo đường dẫn luôn đúng dù include ở đâu
require_once __DIR__ . '/../config/db.php'; 

// 4. Kiểm tra user có bị Admin khóa hoặc chưa duyệt không
$stmt_auth = $conn->prepare("SELECT status FROM users WHERE id = ?");
$stmt_auth->execute([$_SESSION['user_id']]);
$user_status = $stmt_auth->fetchColumn();

if ($user_status == 0) {
    // Nếu status = 0 (Chưa duyệt/Bị khóa) -> Thông báo và đẩy ra trang chủ
    echo "<script>
        alert('Tài khoản Nhà tuyển dụng của bạn đang chờ Admin phê duyệt hoặc đã bị khóa!'); 
        window.location.href='../index.php';
    </script>";
    exit();
}
?>