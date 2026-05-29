<?php
// File: apply.php
session_start();
include 'config/db.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/upload_validate.php';

// 1. Kiểm tra đăng nhập
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Chặn nhà tuyển dụng tự ứng tuyển (Tránh lỗi logic)
if (isset($_SESSION['role']) && $_SESSION['role'] == 'employer') {
    $_SESSION['swal_icon'] = 'error';
    $_SESSION['swal_title'] = 'Nhà tuyển dụng không thể ứng tuyển!';
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$job_id = isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0;
$cv_type = $_POST['cv_type']; // 'online' hoặc 'upload'
$cover_letter = $_POST['cover_letter'];
$final_cv_path = '';

if ($job_id <= 0 || !csrf_validate('apply_job_form', $_POST['csrf_token'] ?? '')) {
    $_SESSION['swal_icon'] = 'error';
    $_SESSION['swal_title'] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
    header("Location: index.php");
    exit();
}

try {
    // --- [BƯỚC QUAN TRỌNG: TÌM CANDIDATE ID] ---
    // Kiểm tra xem User này đã có hồ sơ Ứng viên trong bảng candidates chưa
    $stmt_cand = $conn->prepare("SELECT id, cv_path FROM candidates WHERE user_id = ?");
    $stmt_cand->execute([$user_id]);
    $candidate = $stmt_cand->fetch();

    if ($candidate) {
        // Nếu có rồi -> Lấy ID thật
        $candidate_id = $candidate['id'];
        $original_cv = $candidate['cv_path'];
    } else {
        // Nếu chưa có -> Tự động tạo hồ sơ ứng viên mới
        $stmt_new = $conn->prepare("INSERT INTO candidates (user_id, title, bio) VALUES (?, ?, ?)");
        $stmt_new->execute([$user_id, 'Ứng viên mới', 'Chưa cập nhật thông tin']);
        $candidate_id = $conn->lastInsertId(); // Lấy ID vừa tạo ra
        $original_cv = null;
    }

    // 2. Kiểm tra xem đã apply job này chưa (Tránh spam)
    $check = $conn->prepare("SELECT id FROM applications WHERE job_id = ? AND candidate_id = ?");
    $check->execute([$job_id, $candidate_id]); 
    if ($check->rowCount() > 0) {
        $_SESSION['swal_icon'] = 'warning';
        $_SESSION['swal_title'] = 'Bạn đã ứng tuyển công việc này rồi!';
        header("Location: job-detail.php?id=$job_id");
        exit();
    }

    // 3. Xử lý file CV
    if ($cv_type == 'upload') {
        $cvCheck = upload_validate($_FILES['new_cv'] ?? [], 'cv');
        if (!$cvCheck['ok']) {
            throw new Exception($cvCheck['message']);
        }

        $target_dir = 'uploads/cv/';
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $ext = $cvCheck['extension'];
        $new_name = 'cv_apply_' . $candidate_id . '_' . $job_id . '_' . time() . '.' . $ext;
        $dest = $target_dir . $new_name;

        if (!move_uploaded_file($_FILES['new_cv']['tmp_name'], $dest)) {
            throw new Exception('Lỗi không thể lưu file CV lên server.');
        }
        $final_cv_path = $dest;
    } else {
        // Trường hợp dùng CV Online
        if ($original_cv && file_exists($original_cv)) {
             // Tạo thư mục nếu chưa có
            $target_dir = "uploads/cv/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

            // COPY file cũ ra file mới (Snapshot)
            $ext = pathinfo($original_cv, PATHINFO_EXTENSION);
            $new_name = "cv_apply_" . $candidate_id . "_" . $job_id . "_" . time() . "." . $ext;
            $dest = $target_dir . $new_name;
            
            if (copy($original_cv, $dest)) {
                $final_cv_path = $dest;
            } else {
                $final_cv_path = $original_cv; // Nếu copy lỗi thì dùng tạm file gốc
            }
        } else {
            throw new Exception("Bạn chưa có CV Online. Vui lòng chọn Tải lên CV mới!");
        }
    }

    // 4. Lưu vào CSDL (Bây giờ $candidate_id chắc chắn đã tồn tại)
    $stmt = $conn->prepare("INSERT INTO applications (job_id, candidate_id, cv_snapshot, cover_letter) VALUES (?, ?, ?, ?)");
    $stmt->execute([$job_id, $candidate_id, $final_cv_path, $cover_letter]);

    $_SESSION['swal_icon'] = 'success';
    $_SESSION['swal_title'] = 'Ứng tuyển thành công!';

} catch (PDOException $e) {
    // SQLSTATE 23000 / error 1062: trùng dữ liệu với UNIQUE KEY (job_id, candidate_id)
    $is_duplicate_apply = ($e->getCode() === '23000' && isset($e->errorInfo[1]) && (int)$e->errorInfo[1] === 1062);
    if ($is_duplicate_apply) {
        $_SESSION['swal_icon'] = 'warning';
        $_SESSION['swal_title'] = 'Bạn đã ứng tuyển công việc này rồi!';
    } else {
        $_SESSION['swal_icon'] = 'error';
        $_SESSION['swal_title'] = 'Lỗi hệ thống, vui lòng thử lại.';
    }
} catch (Exception $e) {
    $_SESSION['swal_icon'] = 'error';
    $_SESSION['swal_title'] = 'Lỗi: ' . $e->getMessage();
}

header("Location: job-detail.php?id=$job_id");
exit();
?>