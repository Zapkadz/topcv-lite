<?php
// --- PHẦN 1: XỬ LÝ LOGIC (Đặt trên cùng) ---
if (session_status() === PHP_SESSION_NONE) session_start();
include '../config/db.php';
include 'auth_check.php'; // Kiểm tra quyền

$user_id = $_SESSION['user_id'];

// XỬ LÝ FORM KHI NGƯỜI DÙNG BẤM LƯU
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $address = $_POST['address'];
    $description = $_POST['description'];
    
    // Xử lý Logo
    $logo_path = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $new_name = "company_" . $user_id . "_" . time() . "." . $ext;
        
        // Tự động tạo thư mục nếu chưa có (Tránh lỗi move_uploaded_file)
        $upload_dir = "../uploads/logos/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $dest = $upload_dir . $new_name;
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
            $logo_path = "uploads/logos/" . $new_name;
        }
    }

    // Kiểm tra xem user đã có công ty chưa để Insert hoặc Update
    $check = $conn->prepare("SELECT id FROM companies WHERE user_id = ?");
    $check->execute([$user_id]);
    
    if ($check->rowCount() > 0) {
        // UPDATE (Cập nhật)
        $sql = "UPDATE companies SET name=?, address=?, description=?";
        $params = [$name, $address, $description];
        
        // Chỉ cập nhật logo nếu người dùng có tải ảnh mới lên
        if ($logo_path) {
            $sql .= ", logo=?";
            $params[] = $logo_path;
        }
        $sql .= " WHERE user_id=?";
        $params[] = $user_id;
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
    } else {
        // INSERT (Tạo mới)
        $stmt = $conn->prepare("INSERT INTO companies (user_id, name, address, description, logo) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $name, $address, $description, $logo_path]);
    }

    // Thông báo thành công
    $_SESSION['swal_icon'] = 'success';
    $_SESSION['swal_title'] = 'Lưu thông tin công ty thành công!';
    
    // CHUYỂN HƯỚNG (Được phép chạy vì chưa có HTML nào được xuất ra)
    header("Location: dashboard.php"); 
    exit();
}

// --- PHẦN 2: GIAO DIỆN (HTML) ---
include '../includes/header.php';

// Lấy dữ liệu công ty hiện tại để điền vào form (nếu có)
$stmt = $conn->prepare("SELECT * FROM companies WHERE user_id = ?");
$stmt->execute([$user_id]);
$company = $stmt->fetch();
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-lg">
                <div class="card-header bg-primary text-white p-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-building"></i> Hồ sơ Công ty</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tên công ty</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($company['name'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Logo công ty</label>
                            <?php if(!empty($company['logo'])): ?>
                                <div class="mb-2">
                                    <img src="../<?= $company['logo'] ?>" width="80" class="border rounded">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="logo" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Địa chỉ trụ sở</label>
                            <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($company['address'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Giới thiệu công ty</label>
                            <textarea name="description" class="form-control" rows="5"><?= htmlspecialchars($company['description'] ?? '') ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary fw-bold w-100">Lưu thông tin</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>