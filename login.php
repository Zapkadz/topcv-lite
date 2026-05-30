<?php
session_start();
include 'config/db.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/repositories/UserRepository.php';
require_once __DIR__ . '/includes/services/UserModerationService.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header('Location: admin/index.php');
    } else {
        header('Location: index.php');
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!csrf_validate('login_form', $_POST['csrf_token'] ?? '')) {
        $error = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
    } else {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        $user = UserRepository::findByEmail($conn, $email);

        if ($user && password_verify($password, $user['password'])) {
            $access = UserModerationService::validateLoginAccess($user);
            if (!$access['ok']) {
                $error = $access['message'];
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['role'] = $user['role'];

                $notice = UserModerationService::loginNoticeForUser($user);
                if ($notice) {
                    $_SESSION['login_notice'] = $notice;
                }

                if ($user['role'] == 'admin') {
                    header('Location: admin/index.php');
                } else {
                    header('Location: index.php');
                }
                exit();
            }
        } else {
            $error = 'Email hoặc mật khẩu không chính xác!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập hệ thống</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>

<body>

    <div class="login-container">
        <div class="login-card">
            <h3>TOPCV LITE</h3>

            <?php if ($error): ?>
                <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['register_success'])): ?>
                <div class="alert alert-success text-center">
                    <?= htmlspecialchars($_SESSION['register_success']) ?>
                </div>
                <?php unset($_SESSION['register_success']); ?>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('login_form')) ?>">
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="admin@topcv.local" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Mật khẩu</label>
                    <input type="password" name="password" class="form-control" placeholder="******" required>
                </div>

                <button type="submit" class="btn btn-primary">Đăng nhập</button>
            </form>

            <div class="text-center mt-3">
                <small>Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></small>
            </div>
        </div>
    </div>

</body>

</html>
