<?php
// --- PHẦN 1: LOGIC PHP ---
if (session_status() === PHP_SESSION_NONE) session_start();
include '../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/job_rules.php';
require_once __DIR__ . '/../includes/location_picker.php';
require_once __DIR__ . '/../includes/html_content.php';
require_once __DIR__ . '/../includes/services/JobService.php';
include 'auth_check.php';

$user_id = $_SESSION['user_id'];

// 1. Kiểm tra tham số ID và Ref Page
if (!isset($_GET['id'])) {
    header("Location: manage-jobs.php");
    exit();
}
$job_id = intval($_GET['id']);
$ref_page = isset($_GET['ref_page']) ? intval($_GET['ref_page']) : 1;

// 2. Lấy thông tin Job và xác thực quyền sở hữu
$stmt = $conn->prepare('SELECT id FROM companies WHERE user_id = ? LIMIT 1');
$stmt->execute([$user_id]);
$companyRow = $stmt->fetch();
if (!$companyRow) {
    header('Location: company.php');
    exit();
}
$company_id_edit = (int) $companyRow['id'];

$editCheck = JobService::assertEditableByCompany($conn, $job_id, $company_id_edit);
if (!$editCheck['ok']) {
    $_SESSION['swal_icon'] = 'warning';
    $_SESSION['swal_title'] = $editCheck['message'];
    header('Location: manage-jobs.php?page=' . $ref_page);
    exit();
}

$stmt = $conn->prepare(
    'SELECT j.* FROM jobs j WHERE j.id = ? AND j.company_id = ? LIMIT 1'
);
$stmt->execute([$job_id, $company_id_edit]);
$job = $stmt->fetch();

// 3. Chuẩn bị dữ liệu cho các Select Box
$cats = $conn->query("SELECT * FROM categories")->fetchAll();
$locs = $conn->query('SELECT * FROM locations ORDER BY name ASC')->fetchAll();

// Các mảng dữ liệu cố định
$job_types = ['Toàn thời gian', 'Bán thời gian', 'Thực tập', 'Freelance', 'Hợp đồng'];
// SỬA: Đổi tên biến $ranks thành $job_levels cho đúng ngữ nghĩa DB
$job_levels = ['Nhân viên', 'Trưởng nhóm', 'Trưởng phòng', 'Phó giám đốc', 'Giám đốc', 'Thực tập sinh'];
$experiences = ['Không yêu cầu', 'Dưới 1 năm', '1 năm', '2 năm', '3 năm', '4 năm', '5 năm', 'Trên 5 năm'];
$genders = ['Không yêu cầu', 'Nam', 'Nữ'];

// 4. XỬ LÝ LƯU DỮ LIỆU (UPDATE)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!csrf_validate('employer_job_edit_form', $_POST['csrf_token'] ?? '')) {
        $_SESSION['swal_icon'] = 'error';
        $_SESSION['swal_title'] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
        header('Location: job-edit.php?id=' . $job_id . '&ref_page=' . $ref_page);
        exit();
    }
    // Lấy dữ liệu từ Form
    $title = trim($_POST['title']);
    $category_id = $_POST['category_id'];
    $location_id = $_POST['location_id'];
    $job_type = $_POST['job_type'];
    
    // SỬA: Lấy từ POST key là job_level thay vì rank
    $job_level = $_POST['job_level']; 
    
    $quantity = intval($_POST['quantity']);
    $experience = $_POST['experience'];
    $gender = $_POST['gender'];
    $salary_range = trim($_POST['salary_range']);
    $deadline = $_POST['deadline'];
    $deadlineCheck = job_validate_deadline($deadline);
    if (!$deadlineCheck['ok']) {
        $_SESSION['swal_icon'] = 'error';
        $_SESSION['swal_title'] = $deadlineCheck['message'];
        header('Location: job-edit.php?id=' . $job_id . '&ref_page=' . $ref_page);
        exit();
    }
    $description = $_POST['description'];
    $requirements = $_POST['requirements'];
    $benefits = $_POST['benefits'];

    // SỬA: Câu SQL dùng đúng cột job_level và thêm updated_at
    $sql = "UPDATE jobs SET 
            title=?, category_id=?, location_id=?, 
            job_type=?, job_level=?, quantity=?, experience=?, gender=?, 
            salary_range=?, deadline=?, 
            description=?, requirements=?, benefits=?, 
            status='pending', updated_at=NOW() 
            WHERE id=?";
    
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([
        $title, $category_id, $location_id,
        $job_type, $job_level, $quantity, $experience, $gender,
        $salary_range, $deadline,
        $description, $requirements, $benefits,
        $job_id
    ]);

    if ($result) {
        $_SESSION['swal_icon'] = 'success';
        $_SESSION['swal_title'] = 'Cập nhật thành công! Tin đang chờ duyệt lại.';
        header("Location: manage-jobs.php?page=" . $ref_page);
        exit();
    } else {
        $error = "Có lỗi xảy ra, vui lòng thử lại.";
    }
}

// --- PHẦN 2: GIAO DIỆN HTML ---
include '../includes/header.php';
?>

<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="manage-jobs.php?page=<?= $ref_page ?>">Quản lý tin</a></li>
            <li class="breadcrumb-item active" aria-current="page">Sửa tin tuyển dụng</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-primary text-white p-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-edit me-2"></i> Chỉnh sửa: <?= htmlspecialchars($job['title']) ?></h5>
                </div>
                
                <div class="card-body p-4">
                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('employer_job_edit_form')) ?>">
                        <h6 class="text-primary fw-bold mb-3 border-bottom pb-2">1. Thông tin chung</h6>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">Tiêu đề tin tuyển dụng <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control form-control-lg" value="<?= htmlspecialchars($job['title']) ?>" required placeholder="Ví dụ: Nhân viên Kinh Doanh Bất Động Sản...">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Ngành nghề <span class="text-danger">*</span></label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">-- Chọn ngành nghề --</option>
                                    <?php foreach($cats as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= $c['id'] == $job['category_id'] ? 'selected' : '' ?>><?= $c['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <?php location_picker_render($locs, (int) $job['location_id'], 'location_id', 'Khu vực làm việc'); ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Mức lương <span class="text-danger">*</span></label>
                                <input type="text" name="salary_range" class="form-control" value="<?= htmlspecialchars($job['salary_range']) ?>" required placeholder="VD: 10 - 15 Triệu hoặc Thỏa thuận">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Hạn nộp hồ sơ <span class="text-danger">*</span></label>
                                <input type="date" name="deadline" class="form-control" min="<?= job_today_date() ?>" value="<?= date('Y-m-d', strtotime($job['deadline'])) ?>" required
                                       title="<?= htmlspecialchars(job_deadline_past_message()) ?>"
                                       oninvalid="this.setCustomValidity('<?= htmlspecialchars(job_deadline_past_message(), ENT_QUOTES) ?>')"
                                       oninput="this.setCustomValidity('')">
                                <div class="form-text text-muted">Phải là hôm nay hoặc một ngày sau — không nhập ngày trong quá khứ.</div>
                            </div>
                        </div>

                        <h6 class="text-primary fw-bold mt-4 mb-3 border-bottom pb-2">2. Yêu cầu chi tiết</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Hình thức làm việc</label>
                                <select name="job_type" class="form-select">
                                    <?php foreach($job_types as $type): ?>
                                        <option value="<?= $type ?>" <?= $type == $job['job_type'] ? 'selected' : '' ?>><?= $type ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Cấp bậc</label>
                                <select name="job_level" class="form-select">
                                    <?php foreach($job_levels as $lvl): ?>
                                        <option value="<?= $lvl ?>" <?= $lvl == $job['job_level'] ? 'selected' : '' ?>><?= $lvl ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Kinh nghiệm</label>
                                <select name="experience" class="form-select">
                                    <?php foreach($experiences as $exp): ?>
                                        <option value="<?= $exp ?>" <?= (isset($job['experience']) && $exp == $job['experience']) ? 'selected' : '' ?>><?= $exp ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Số lượng tuyển</label>
                                <input type="number" name="quantity" class="form-control" min="1" value="<?= isset($job['quantity']) ? $job['quantity'] : 1 ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Giới tính yêu cầu</label>
                                <div class="mt-2">
                                    <?php foreach($genders as $g): ?>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="gender" id="g_<?= $g ?>" value="<?= $g ?>" <?= (isset($job['gender']) && $g == $job['gender']) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="g_<?= $g ?>"><?= $g ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <h6 class="text-primary fw-bold mt-4 mb-3 border-bottom pb-2">3. Nội dung mô tả</h6>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Mô tả công việc <span class="text-danger">*</span></label>
                            <textarea name="description" id="description" class="form-control" required><?= textarea_editor_content($job['description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Yêu cầu ứng viên</label>
                            <textarea name="requirements" id="requirements" class="form-control"><?= textarea_editor_content($job['requirements'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Quyền lợi được hưởng</label>
                            <textarea name="benefits" id="benefits" class="form-control"><?= textarea_editor_content($job['benefits'] ?? '') ?></textarea>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <a href="manage-jobs.php?page=<?= $ref_page ?>" class="btn btn-secondary px-4">Hủy bỏ</a>
                            <button type="submit" class="btn btn-primary fw-bold px-4">
                                <i class="fas fa-save me-1"></i> Lưu thay đổi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.ckeditor.com/ckeditor5/40.2.0/classic/ckeditor.js"></script>
<script>
    const commonConfig = {
        toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'blockQuote', 'undo', 'redo'],
        placeholder: 'Nhập nội dung chi tiết tại đây...'
    };

    ClassicEditor.create(document.querySelector('#description'), commonConfig).catch(console.error);
    ClassicEditor.create(document.querySelector('#requirements'), commonConfig).catch(console.error);
    ClassicEditor.create(document.querySelector('#benefits'), commonConfig).catch(console.error);
</script>

<?php include '../includes/footer.php'; ?>