<?php
// --- PHẦN 1: LOGIC KIỂM TRA & XỬ LÝ (Chạy trước khi xuất HTML) ---
if (session_status() === PHP_SESSION_NONE) session_start();
include '../config/db.php'; // Cần include db trước để dùng biến $conn
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/upload_validate.php';
require_once __DIR__ . '/../includes/schema_cvs.php';
require_once __DIR__ . '/../includes/cv_rules.php';
require_once __DIR__ . '/../includes/services/CvService.php';

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
    if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $cvCheck = upload_validate($_FILES['cv_file'], 'cv');
        if (!$cvCheck['ok']) {
            $_SESSION['swal_icon'] = 'error';
            $_SESSION['swal_title'] = $cvCheck['message'];
            header('Location: profile.php');
            exit();
        }

        $upload_dir = '../uploads/cv/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $ext = $cvCheck['extension'];
        $new_name = 'cv_base_' . $user_id . '_' . time() . '.' . $ext;
        $dest = $upload_dir . $new_name;
        if (!move_uploaded_file($_FILES['cv_file']['tmp_name'], $dest)) {
            $_SESSION['swal_icon'] = 'error';
            $_SESSION['swal_title'] = 'Lỗi không thể lưu file CV lên server.';
            header('Location: profile.php');
            exit();
        }
        $cv_path = 'uploads/cv/' . $new_name;
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

$onlineCvs = [];
if (cvs_schema_ready($conn)) {
    $onlineCvs = array_slice(CvService::listForUser($conn, $user_id), 0, 5);
}

// --- PHẦN 2: GIAO DIỆN HTML (Bắt đầu xuất dữ liệu từ đây) ---
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <?php if (cvs_schema_ready($conn)): ?>
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0"><i class="fas fa-file-alt text-success"></i> CV online</h5>
                    <a href="cv-manage.php" class="btn btn-sm btn-outline-success">Xem tất cả</a>
                </div>
                <div class="card-body px-4 pb-4">
                    <?php if (count($onlineCvs) === 0): ?>
                        <p class="text-muted mb-3">Chưa có CV online. Tạo CV có cấu trúc để ứng tuyển nhanh hơn.</p>
                        <a href="cv-builder.php" class="btn btn-success btn-sm fw-bold">Tạo CV online</a>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($onlineCvs as $cv): ?>
                                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="fw-bold"><?= htmlspecialchars((string) $cv['title']) ?></span>
                                        <?php if ((int) ($cv['is_primary'] ?? 0) === 1): ?>
                                            <span class="badge bg-success ms-1">Mặc định</span>
                                        <?php endif; ?>
                                        <br>
                                        <small class="text-muted">Cập nhật: <?= htmlspecialchars(cv_format_updated_at($cv['updated_at'] ?? null)) ?></small>
                                    </div>
                                    <a href="cv-builder.php?id=<?= (int) $cv['id'] ?>" class="btn btn-sm btn-outline-secondary">Sửa</a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

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