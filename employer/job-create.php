<?php
// --- PHẦN 1: LOGIC PHP ---
if (session_status() === PHP_SESSION_NONE) session_start();
include '../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/job_rules.php';
require_once __DIR__ . '/../includes/location_picker.php';
include 'auth_check.php';

$user_id = $_SESSION['user_id'];

// 1. Kiểm tra hồ sơ công ty
$stmt = $conn->prepare("SELECT id FROM companies WHERE user_id = ?");
$stmt->execute([$user_id]);
$company = $stmt->fetch();

if (!$company) {
    echo "<script>alert('Vui lòng cập nhật hồ sơ công ty trước khi đăng tin!'); window.location.href='company.php';</script>";
    exit();
}

// 2. Định nghĩa các mảng dữ liệu (Select box)
$cats = $conn->query("SELECT * FROM categories")->fetchAll();
$locs = $conn->query('SELECT * FROM locations ORDER BY name ASC')->fetchAll();

$job_types = ['Toàn thời gian', 'Bán thời gian', 'Thực tập', 'Freelance', 'Remote'];
$job_levels = ['Nhân viên', 'Thực tập sinh', 'Trưởng nhóm', 'Quản lý', 'Giám đốc'];
$experiences = ['Không yêu cầu', 'Dưới 1 năm', '1 năm', '2 năm', '3 năm', '4 năm', '5 năm', 'Trên 5 năm'];
$genders = ['Không yêu cầu', 'Nam', 'Nữ'];

// 3. XỬ LÝ ĐĂNG TIN
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!csrf_validate('employer_job_create_form', $_POST['csrf_token'] ?? '')) {
        $_SESSION['swal_icon'] = 'error';
        $_SESSION['swal_title'] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
        header('Location: job-create.php');
        exit();
    }
    $title = trim($_POST['title']);
    $category_id = $_POST['category_id'];
    $location_id = $_POST['location_id'];
    $salary_range = trim($_POST['salary_range']);
    $quantity = intval($_POST['quantity']);
    $deadline = $_POST['deadline'];
    $deadlineCheck = job_validate_deadline($deadline);
    if (!$deadlineCheck['ok']) {
        $_SESSION['swal_icon'] = 'error';
        $_SESSION['swal_title'] = $deadlineCheck['message'];
        header('Location: job-create.php');
        exit();
    }

    // Các trường chọn
    $job_type = $_POST['job_type'];
    $job_level = $_POST['job_level'];
    $experience = $_POST['experience']; // Mới thêm
    $gender = $_POST['gender'];         // Mới thêm
    
    // Nội dung từ CKEditor
    $description = $_POST['description'];
    $requirements = $_POST['requirements'];
    $benefits = $_POST['benefits'];
    
    // SQL Insert đầy đủ
    $sql = "INSERT INTO jobs (
                company_id, category_id, location_id, 
                title, salary_range, quantity, deadline,
                job_type, job_level, experience, gender,
                description, requirements, benefits, 
                status, created_at, updated_at
            ) VALUES (
                ?, ?, ?, 
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, 
                'pending', NOW(), NOW()
            )";
    
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([
        $company['id'], $category_id, $location_id,
        $title, $salary_range, $quantity, $deadline,
        $job_type, $job_level, $experience, $gender,
        $description, $requirements, $benefits
    ]);

    if ($result) {
        $_SESSION['swal_icon'] = 'success';
        $_SESSION['swal_title'] = 'Đăng tin thành công! Vui lòng chờ Admin duyệt.';
        header("Location: manage-jobs.php"); // Quay về trang quản lý tin
        exit();
    } else {
        echo "<script>alert('Có lỗi xảy ra, vui lòng thử lại!');</script>";
    }
}

include '../includes/header.php';
?>

<style>
    .ck-editor__editable_inline { min-height: 150px; }
    .form-label { font-weight: 600; color: #333; }
</style>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="manage-jobs.php">Quản lý tin</a></li>
                <li class="breadcrumb-item active">Đăng tin mới</li>
            </ol>
        </nav>
        <a href="manage-jobs.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Quay lại danh sách
        </a>
    </div>

    <div class="card border-0 shadow-lg rounded-3">
        <div class="card-header bg-success text-white fw-bold p-3">
            <i class="fas fa-plus-circle me-2"></i> Đăng tin tuyển dụng mới
        </div>
        <div class="card-body p-4">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('employer_job_create_form')) ?>">
                <h6 class="text-success fw-bold border-bottom pb-2 mb-3">1. Thông tin chung</h6>
                
                <div class="mb-3">
                    <label class="form-label">Tiêu đề công việc <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control form-control-lg" placeholder="VD: Nhân viên Kinh doanh Bất động sản..." required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Ngành nghề <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select" required>
                            <option value="">-- Chọn ngành nghề --</option>
                            <?php foreach($cats as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <?php location_picker_render($locs); ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Mức lương <span class="text-danger">*</span></label>
                        <input type="text" name="salary_range" class="form-control" placeholder="VD: 10 - 15 triệu hoặc Thỏa thuận" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Hạn nộp hồ sơ <span class="text-danger">*</span></label>
                        <input type="date" name="deadline" class="form-control" min="<?= job_today_date() ?>" required
                               title="<?= htmlspecialchars(job_deadline_past_message()) ?>"
                               oninvalid="this.setCustomValidity('<?= htmlspecialchars(job_deadline_past_message(), ENT_QUOTES) ?>')"
                               oninput="this.setCustomValidity('')">
                        <div class="form-text text-muted">Phải là hôm nay hoặc một ngày sau — không nhập ngày trong quá khứ.</div>
                    </div>
                </div>

                <h6 class="text-success fw-bold border-bottom pb-2 mt-4 mb-3">2. Yêu cầu chi tiết</h6>
                
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Hình thức</label>
                        <select name="job_type" class="form-select">
                            <?php foreach($job_types as $jt): ?>
                                <option value="<?= $jt ?>"><?= $jt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Cấp bậc</label>
                        <select name="job_level" class="form-select">
                            <?php foreach($job_levels as $jl): ?>
                                <option value="<?= $jl ?>"><?= $jl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Kinh nghiệm</label>
                        <select name="experience" class="form-select">
                            <?php foreach($experiences as $exp): ?>
                                <option value="<?= $exp ?>"><?= $exp ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Giới tính</label>
                        <select name="gender" class="form-select">
                            <?php foreach($genders as $g): ?>
                                <option value="<?= $g ?>"><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                     <div class="col-md-3 mb-3">
                        <label class="form-label">Số lượng tuyển</label>
                        <input type="number" name="quantity" class="form-control" value="1" min="1" required>
                    </div>
                </div>

                <h6 class="text-success fw-bold border-bottom pb-2 mt-4 mb-3">3. Nội dung mô tả</h6>

                <div class="mb-3">
                    <label class="form-label">Mô tả công việc <span class="text-danger">*</span></label>
                    <textarea name="description" id="description" class="form-control"></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Yêu cầu ứng viên</label>
                    <textarea name="requirements" id="requirements" class="form-control"></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Quyền lợi được hưởng</label>
                    <textarea name="benefits" id="benefits" class="form-control"></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="manage-jobs.php" class="btn btn-secondary px-4">Hủy bỏ</a>
                    <button type="submit" class="btn btn-success fw-bold px-4">
                        <i class="fas fa-paper-plane me-1"></i> Hoàn tất & Đăng tin
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.ckeditor.com/ckeditor5/40.2.0/classic/ckeditor.js"></script>
<script>
    const commonConfig = {
        toolbar: [ 'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'blockQuote', 'undo', 'redo' ],
        placeholder: 'Nhập nội dung chi tiết tại đây...'
    };

    ClassicEditor.create( document.querySelector('#description'), commonConfig ).catch( error => { console.error( error ); } );
    ClassicEditor.create( document.querySelector('#requirements'), commonConfig ).catch( error => { console.error( error ); } );
    ClassicEditor.create( document.querySelector('#benefits'), commonConfig ).catch( error => { console.error( error ); } );
</script>

<?php include '../includes/footer.php'; ?>