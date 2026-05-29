<?php
session_start();
include 'config/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role']; // 'candidate' hoặc 'employer'

    // 1. Validate cơ bản
    if ($password !== $confirm_password) {
        $error = "Mật khẩu nhập lại không khớp!";
    } else {
        // 2. Kiểm tra email đã tồn tại chưa
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $error = "Email này đã được sử dụng. Vui lòng chọn email khác!";
        } else {
            // 3. LOGIC QUAN TRỌNG: Xét trạng thái
            // Nhà tuyển dụng (employer) -> status = 0 (Chờ duyệt)
            // Ứng viên (candidate) -> status = 1 (Hoạt động luôn)
            $status = ($role == 'employer') ? 0 : 1;
            
            // Mã hóa mật khẩu
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert vào DB
            $sql = "INSERT INTO users (fullname, email, password, role, status) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            
            if ($stmt->execute([$fullname, $email, $hashed_password, $role, $status])) {
                // Đăng ký thành công -> Chuyển hướng về Login
                $msg = ($role == 'employer') 
                    ? "Đăng ký thành công! Tài khoản Nhà tuyển dụng cần chờ Admin duyệt." 
                    : "Đăng ký thành công! Bạn có thể đăng nhập ngay.";
                
                // Dùng Session để báo tin bên trang login
                $_SESSION['register_success'] = $msg;
                header("Location: login.php");
                exit();
            } else {
                $error = "Có lỗi xảy ra, vui lòng thử lại!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký tài khoản - TopCV Lite</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .register-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card-register { background: white; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); width: 100%; max-width: 500px; overflow: hidden; }
        .register-header { background: #00b14f; padding: 25px; text-align: center; color: white; }
        .register-header h4 { margin: 0; font-weight: 700; }
        .form-control { padding: 10px 15px; border-radius: 6px; background-color: #f9f9f9; }
        .form-control:focus { background-color: #fff; border-color: #00b14f; box-shadow: 0 0 0 3px rgba(0, 177, 79, 0.2); }
        
        /* Style cho phần chọn Role */
        .role-selector { display: flex; gap: 15px; margin-bottom: 20px; }
        .role-option { flex: 1; }
        .role-option input { display: none; } /* Ẩn radio mặc định */
        .role-label {
            display: block; padding: 15px; border: 2px solid #e0e0e0; border-radius: 8px;
            text-align: center; cursor: pointer; transition: all 0.2s;
            color: #555; font-weight: 600;
        }
        .role-label i { display: block; font-size: 24px; margin-bottom: 8px; color: #aaa; }
        
        /* Khi được chọn */
        .role-option input:checked + .role-label {
            border-color: #00b14f; background-color: #e5f7ed; color: #00b14f;
        }
        .role-option input:checked + .role-label i { color: #00b14f; }

        .btn-success-custom { background: #00b14f; border: none; padding: 12px; font-weight: 700; width: 100%; border-radius: 6px; }
        .btn-success-custom:hover { background: #009643; }
    </style>
</head>
<body>

<div class="register-container">
    <div class="card-register">
        <div class="register-header">
            <h4><i class="fas fa-user-plus"></i> Đăng Ký Tài Khoản</h4>
            <p class="small mb-0 opacity-75">Gia nhập hệ thống tuyển dụng hàng đầu</p>
        </div>
        
        <div class="p-4">
            <?php if($error): ?>
                <div class="alert alert-danger text-center p-2 mb-3 small"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <label class="form-label fw-bold mb-2">Bạn là ai?</label>
                <div class="role-selector">
                    <div class="role-option">
                        <input type="radio" name="role" id="role_candidate" value="candidate" checked>
                        <label class="role-label" for="role_candidate">
                            <i class="fas fa-user-graduate"></i>
                            Ứng viên
                        </label>
                    </div>
                    <div class="role-option">
                        <input type="radio" name="role" id="role_employer" value="employer">
                        <label class="role-label" for="role_employer">
                            <i class="fas fa-building"></i>
                            Nhà tuyển dụng
                        </label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Họ và tên</label>
                    <input type="text" name="fullname" class="form-control" placeholder="Ví dụ: Nguyễn Văn A" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Email đăng nhập</label>
                    <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Mật khẩu</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Nhập lại mật khẩu</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-success-custom mt-3">
                    Đăng Ký Ngay
                </button>
            </form>

            <div class="text-center mt-4">
                <small>Bạn đã có tài khoản? <a href="login.php" class="text-success fw-bold text-decoration-none">Đăng nhập</a></small>
            </div>
        </div>
    </div>
</div>

</body>
</html>