<?php
ob_start();
include '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
// Lấy tên file hiện tại để active menu
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-color: #00b14f;
            /* Xanh lá chủ đạo */
            --primary-light: #e5f7ed;
            /* Nền xanh nhạt */
            --text-dark: #2c3e50;
            --shadow-soft: 0 5px 15px rgba(0, 0, 0, 0.05);
            /* Bóng mờ */
            --radius: 12px;
        }

        body {
            background-color: #f7f9fa;
            font-family: 'Segoe UI', sans-serif;
            color: var(--text-dark);
        }

        /* Sidebar mới */
        .sidebar {
            width: 260px;
            background: white;
            min-height: 100vh;
            box-shadow: 2px 0 20px rgba(0, 0, 0, 0.03);
            position: fixed;
            padding: 20px;
            z-index: 100;
        }

        .sidebar h4 {
            color: var(--primary-color);
            font-weight: 800;
            text-align: center;
            margin-bottom: 30px;
            letter-spacing: 1px;
        }

        .nav-link {
            color: #666;
            padding: 12px 15px;
            border-radius: var(--radius);
            margin-bottom: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            background-color: var(--primary-light);
            color: var(--primary-color);
            transform: translateX(5px);
            box-shadow: var(--shadow-soft);
        }

        .nav-link i {
            width: 25px;
        }

        /* Content chính */
        .main-content {
            margin-left: 260px;
            padding: 30px;
        }

        /* Card & Table Custom */
        .card,
        .table-responsive,
        .modal-content {
            border: none;
            border-radius: var(--radius);
            box-shadow: var(--shadow-soft);
            background: white;
        }

        .table thead th {
            background-color: var(--primary-light);
            color: var(--primary-color);
            border: none;
            padding: 15px;
            font-weight: 600;
        }

        .table td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f0;
        }

        /* Button Custom */
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            box-shadow: 0 4px 10px rgba(0, 177, 79, 0.3);
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
        }

        .btn-primary:hover {
            background-color: #009643;
            transform: translateY(-2px);
        }

        /* Input Custom */
        .form-control {
            border-radius: 8px;
            border: 1px solid #eee;
            padding: 10px 15px;
            background-color: #fcfcfc;
        }

        .form-control:focus {
            box-shadow: 0 0 0 3px var(--primary-light);
            border-color: var(--primary-color);
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <h4><i class="fas fa-leaf"></i> TOPCV ADMIN</h4>
        <nav class="nav flex-column">
            <a href="index.php" class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-pie"></i> Tổng quan
            </a>
            <a href="jobs.php" class="nav-link <?= $current_page == 'jobs.php' ? 'active' : '' ?>">
                <i class="fas fa-briefcase"></i> Tin tuyển dụng
            </a>
            <a href="categories.php" class="nav-link <?= $current_page == 'categories.php' ? 'active' : '' ?>">
                <i class="fas fa-folder-open"></i> Danh mục ngành
            </a>
            <a href="locations.php" class="nav-link <?= $current_page == 'locations.php' ? 'active' : '' ?>">
                <i class="fas fa-map-marker-alt"></i> Địa điểm
            </a>
            <a href="users.php" class="nav-link <?= $current_page == 'users.php' ? 'active' : '' ?>">
                <i class="fas fa-users"></i> Người dùng
            </a>
            <a href="moderation-log.php" class="nav-link <?= $current_page == 'moderation-log.php' ? 'active' : '' ?>">
                <i class="fas fa-clipboard-list"></i> Nhật ký duyệt
            </a>
            <?php
            $taxonomyNavPages = ['ai_taxonomy_suggestions.php', 'ai_taxonomy_suggestion_import.php', 'ai_taxonomy_suggestion_review.php'];
            $taxonomyNavActive = in_array($current_page, $taxonomyNavPages, true);
            ?>
            <a href="ai_taxonomy_suggestions.php" class="nav-link <?= $taxonomyNavActive ? 'active' : '' ?>">
                <i class="fas fa-tags"></i> Taxonomy AI
            </a>
            <a href="../logout.php" class="nav-link text-danger mt-4">
                <i class="fas fa-sign-out-alt"></i> Đăng xuất
            </a>
        </nav>
    </div>

    <div class="main-content">
        <div class="card p-4 mb-4">