<?php
// Bắt đầu Session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kết nối DB
include __DIR__ . '/../config/db.php';
require_once __DIR__ . '/user_status.php';
require_once __DIR__ . '/repositories/UserRepository.php';
$base_url = '/topcv_lite/';
if (!defined('BASE_URL')) {
    define('BASE_URL', $base_url);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TopCV Lite - Tiếp lợi thế, nối thành công</title>
    <link rel="icon" type="image/png" href="<?= $base_url ?>favicon.ico?v=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root {
            --primary: #00b14f; 
            --primary-hover: #009643;
            --bg-gray: #f4f5f5;
            --text-dark: #333;
        }
        body { background-color: var(--bg-gray); font-family: 'Inter', sans-serif; color: var(--text-dark); }
        .navbar { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 10px 0; }
        .navbar-brand { font-weight: 800; color: var(--primary) !important; font-size: 1.5rem; }
        .nav-link { font-weight: 600; color: #555; margin-right: 15px; }
        .nav-link:hover { color: var(--primary); }
        
        .btn-primary-custom { background-color: var(--primary); color: white; border: none; font-weight: 600; }
        .btn-primary-custom:hover { background-color: var(--primary-hover); color: white; }
        .btn-outline-custom { border: 1px solid var(--primary); color: var(--primary); font-weight: 600; }
        .btn-outline-custom:hover { background-color: var(--primary); color: white; }
        
        .user-avatar { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; background: #eee; }
        .dropdown-menu { border-radius: 8px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light fixed-top">
    <div class="container">
        <a class="navbar-brand" href="<?= $base_url ?>index.php"><i class="fas fa-briefcase"></i> TOPCV LITE</a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>jobs.php">Việc làm</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $base_url ?>candidate/profile.php">Hồ sơ cá nhân</a></li>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'candidate'): ?>
                <li class="nav-item"><a class="nav-link" href="<?= $base_url ?>candidate/cv-manage.php">Quản lý CV online</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>companies.php">Công ty</a></li>
            </ul>
            
            <div class="d-flex align-items-center gap-2">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php
                        $headerUser = UserRepository::findById($conn, (int) $_SESSION['user_id']);
                        $employerPanelOk = $headerUser && user_can_use_employer_panel($headerUser);
                        $employerPending = $headerUser
                            && ($headerUser['role'] ?? '') === 'employer'
                            && ($headerUser['employer_approval_status'] ?? '') === 'pending';
                    ?>

                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-dark fw-bold" data-bs-toggle="dropdown">
                            <img src="https://ui-avatars.com/api/?name=<?= $_SESSION['fullname'] ?>&background=00b14f&color=fff" class="user-avatar me-2">
                            <?= htmlspecialchars($_SESSION['fullname']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end mt-2" style="min-width: 260px;">
                            
                            <?php if ($_SESSION['role'] == 'employer'): ?>
                                <?php if ($employerPanelOk): ?>
                                    <li><a class="dropdown-item text-primary fw-bold" href="<?= $base_url ?>employer/dashboard.php">
                                        <i class="fas fa-chart-line me-2"></i> Trang Nhà tuyển dụng
                                    </a></li>
                                    <li><a class="dropdown-item" href="<?= $base_url ?>employer/job-create.php">
                                        <i class="fas fa-plus-circle me-2"></i> Đăng tin mới
                                    </a></li>
                                <?php elseif ($employerPending): ?>
                                    <li><div class="dropdown-item bg-warning bg-opacity-10 text-warning fw-bold">
                                        <i class="fas fa-clock me-2"></i> Đang chờ duyệt...
                                    </div></li>
                                    <li><div class="dropdown-item small text-muted text-wrap fst-italic">
                                        Tài khoản cần Admin duyệt mới được đăng tin.
                                    </div></li>
                                <?php else: ?>
                                    <li><div class="dropdown-item small text-muted text-wrap fst-italic">
                                        Tài khoản Nhà tuyển dụng chưa được kích hoạt.
                                    </div></li>
                                <?php endif; ?>

                            <?php elseif($_SESSION['role'] == 'admin'): ?>
                                <li><a class="dropdown-item text-danger fw-bold" href="<?= $base_url ?>admin/index.php">
                                    <i class="fas fa-cogs me-2"></i> Trang Quản trị
                                </a></li>
                            
                            <?php else: ?>
                                <li><a class="dropdown-item" href="<?= $base_url ?>candidate/profile.php">
                                    <i class="fas fa-user-edit me-2"></i> Hồ sơ cá nhân
                                </a></li>
                                <li><a class="dropdown-item" href="<?= $base_url ?>candidate/cv-manage.php">
                                    <i class="fas fa-file-alt me-2"></i> Quản lý CV online
                                </a></li>
                                <li><a class="dropdown-item" href="<?= $base_url ?>candidate/job-recommendations.php">
                                    <i class="fas fa-robot me-2"></i> AI gợi ý việc làm
                                </a></li>
                                <li><a class="dropdown-item" href="<?= $base_url ?>candidate/my-jobs.php">
                                    <i class="fas fa-history me-2"></i> Việc đã nộp
                                </a></li>
                            <?php endif; ?>

                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= $base_url ?>logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Đăng xuất
                            </a></li>
                        </ul>
                    </div>
                
                <?php else: ?>
                    <a href="<?= $base_url ?>login.php" class="btn btn-outline-custom btn-sm px-3">Đăng nhập</a>
                    <a href="<?= $base_url ?>register.php" class="btn btn-primary-custom btn-sm px-3">Đăng ký</a>
                    <a href="#" class="btn btn-dark btn-sm px-3 ms-2">Dành cho Nhà tuyển dụng</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<div style="margin-top: 76px;"></div>