<?php
// --- PHẦN 1: LOGIC KIỂM TRA & XỬ LÝ (Chạy trước khi xuất HTML) ---
if (session_status() === PHP_SESSION_NONE) session_start();
include '../config/db.php'; // Cần include db trước để dùng biến $conn
require_once __DIR__ . '/../includes/csrf.php';

// Bảo mật: Chỉ candidate mới được vào
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'candidate') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy dữ liệu hồ sơ hiện tại để hiển thị an toàn lên form
$stmt_profile = $conn->prepare("SELECT title, cv_path, bio FROM candidates WHERE user_id = ? LIMIT 1");
$stmt_profile->execute([$user_id]);
$profile = $stmt_profile->fetch();
if (!$profile) {
    $profile = ['title' => '', 'cv_path' => '', 'bio' => ''];
}

// Xử lý CẬP NHẬT HỒ SƠ
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!csrf_validate('candidate_profile_form', $_POST['csrf_token'] ?? '')) {
        $_SESSION['swal_icon'] = 'error';
        $_SESSION['swal_title'] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
        header("Location: profile.php");
        exit();
    }

    $title = $_POST['title'];
    $bio = $_POST['bio'];
    
    // Xử lý Upload CV
    $cv_path = null;
    if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] == 0) {
        $allowed = ['pdf', 'doc', 'docx'];
        $ext = strtolower(pathinfo($_FILES['cv_file']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_name = "cv_base_" . $user_id . "_" . time() . "." . $ext;
            $dest = "../uploads/cv/" . $new_name;
            if (move_uploaded_file($_FILES['cv_file']['tmp_name'], $dest)) {
                $cv_path = "uploads/cv/" . $new_name;
            }
        }
    }

    // Kiểm tra xem đã có bản ghi trong bảng candidates chưa
    $check = $conn->prepare("SELECT id FROM candidates WHERE user_id = ?");
    $check->execute([$user_id]);

    if ($check->rowCount() > 0) {
        // Update
        $sql = "UPDATE candidates SET title = ?, bio = ?";
        $params = [$title, $bio];
        
        if ($cv_path) {
            $sql .= ", cv_path = ?";
            $params[] = $cv_path;
        }
        $sql .= " WHERE user_id = ?";
        $params[] = $user_id;
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
    } else {
        // Insert
        $stmt = $conn->prepare("INSERT INTO candidates (user_id, title, bio, cv_path) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $bio, $cv_path]);
    }

    $_SESSION['swal_icon'] = 'success';
    $_SESSION['swal_title'] = 'Cập nhật hồ sơ thành công!';
    header("Location: profile.php");
    exit();
}

// --- PHẦN 2: GIAO DIỆN HTML (Bắt đầu xuất dữ liệu từ đây) ---
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-lg rounded-4">
                <div class="card-header bg-success text-white rounded-top-4 p-4">
                    <h4 class="mb-0 fw-bold"><i class="fas fa-user-edit"></i> Cập nhật hồ sơ cá nhân</h4>
                    <p class="mb-0 opacity-75">Hãy hoàn thiện hồ sơ để nhà tuyển dụng chú ý tới bạn</p>
                </div>
                <div class="card-body p-5">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('candidate_profile_form')) ?>">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Vị trí mong muốn / Chức danh</label>
                            <input type="text" name="title" class="form-control" placeholder="VD: Senior PHP Developer" value="<?= htmlspecialchars($profile['title'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">CV đính kèm (Mặc định)</label>
                            <?php if(!empty($profile['cv_path'])): ?>
                                <div class="mb-2 p-2 bg-light border rounded d-flex align-items-center justify-content-between">
                                    <span class="text-success fw-bold"><i class="fas fa-file-pdf"></i> CV hiện tại</span>
                                    <a href="../<?= $profile['cv_path'] ?>" target="_blank" class="btn btn-sm btn-outline-success">Xem</a>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="cv_file" class="form-control" accept=".pdf,.doc,.docx">
                            <small class="text-muted">Tải lên CV mới để thay thế (PDF/DOC)</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Giới thiệu bản thân (Bio)</label>
                            <textarea name="bio" class="form-control" rows="5" placeholder="Kinh nghiệm, kỹ năng nổi bật..."><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-success w-100 fw-bold py-2">Lưu hồ sơ</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>