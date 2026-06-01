<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/schema_cvs.php';
require_once __DIR__ . '/../includes/cv_rules.php';
require_once __DIR__ . '/../includes/cv_avatar.php';
require_once __DIR__ . '/../includes/services/CvService.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'candidate') {
    header('Location: ../index.php');
    exit();
}

$userId = (int) $_SESSION['user_id'];
$cvId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $cvId > 0;
$schemaReady = cvs_schema_ready($conn);

$profile = [
    'title' => '',
    'full_name' => '',
    'target_position' => '',
    'date_of_birth' => '',
    'gender' => '',
    'phone' => '',
    'email' => '',
    'website' => '',
    'address' => '',
    'career_objective' => '',
    'avatar_path' => '',
];
$educations = [];
$experiences = [];
$skills = [];

if ($schemaReady && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate('candidate_cv_save_form', $_POST['csrf_token'] ?? '')) {
        $_SESSION['swal_icon'] = 'error';
        $_SESSION['swal_title'] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
        header('Location: ' . ($isEdit ? 'cv-builder.php?id=' . $cvId : 'cv-builder.php'));
        exit();
    }

    $parsed = cv_parse_builder_post($_POST);
    $postCvId = isset($_POST['cv_id']) ? (int) $_POST['cv_id'] : 0;
    $currentAvatar = trim((string) ($_POST['avatar_path'] ?? ''));
    $avatarResult = cv_avatar_apply_post($_POST, $_FILES, $userId, $currentAvatar);
    if (!$avatarResult['ok']) {
        $_SESSION['swal_icon'] = 'error';
        $_SESSION['swal_title'] = $avatarResult['message'];
        header('Location: ' . ($postCvId > 0 ? 'cv-builder.php?id=' . $postCvId : 'cv-builder.php'));
        exit();
    }
    $parsed['profile']['avatar_path'] = $avatarResult['path'];

    if ($postCvId > 0) {
        $result = CvService::saveForUser($conn, $userId, $postCvId, $parsed['profile'], $parsed['children']);
        $_SESSION['swal_icon'] = $result['ok'] ? 'success' : 'error';
        $_SESSION['swal_title'] = $result['message'];
        header('Location: ' . ($result['ok'] ? 'cv-manage.php' : 'cv-builder.php?id=' . $postCvId));
        exit();
    }

    $result = CvService::createForUser($conn, $userId, $parsed['profile'], $parsed['children']);
    $_SESSION['swal_icon'] = $result['ok'] ? 'success' : 'error';
    $_SESSION['swal_title'] = $result['message'];
    if ($result['ok'] && !empty($result['cv_id'])) {
        header('Location: cv-manage.php');
        exit();
    }

    $profile = $parsed['profile'];
    $educations = $parsed['children']['educations'];
    $experiences = $parsed['children']['experiences'];
    $skills = $parsed['children']['skills'];
} elseif ($schemaReady && $isEdit && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $loaded = CvService::getFullForUser($conn, $userId, $cvId);
    if (!$loaded['ok'] || !$loaded['data']) {
        $_SESSION['swal_icon'] = 'error';
        $_SESSION['swal_title'] = $loaded['message'];
        header('Location: cv-manage.php');
        exit();
    }
    $profile = $loaded['data']['profile'];
    $educations = $loaded['data']['educations'];
    $experiences = $loaded['data']['experiences'];
    $skills = $loaded['data']['skills'];
    $profile['phone'] = cv_normalize_phone((string) ($profile['phone'] ?? ''));
} elseif ($schemaReady && !$isEdit && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $conn->prepare('SELECT fullname, email, phone FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $userRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($userRow) {
        $profile['full_name'] = (string) ($userRow['fullname'] ?? '');
        $profile['email'] = (string) ($userRow['email'] ?? '');
        $prefillPhone = cv_normalize_phone((string) ($userRow['phone'] ?? ''));
        if (cv_is_valid_phone_vn($prefillPhone)) {
            $profile['phone'] = $prefillPhone;
        }
    }
}

$avatarPreviewUrl = cv_avatar_public_url($profile['avatar_path'] ?? null);
$avatarRules = cv_avatar_rules();
$dobBounds = cv_date_of_birth_bounds();

$pageTitle = $isEdit ? 'Sửa CV online' : 'Tạo CV mới';

/**
 * @param 'start'|'end' $role
 * @param array<string, mixed> $row
 */
function cv_builder_month_year_fields(
    int|string $index,
    string $section,
    string $role,
    array $row,
    bool $required = false
): void {
    $parts = cv_split_year_month($row[$role . '_date'] ?? null);
    $label = $role === 'start' ? 'Bắt đầu' : 'Kết thúc';
    $reqMark = $required ? ' <span class="text-danger">*</span>' : '';
    ?>
    <div class="col-md-3">
        <label class="form-label small"><?= $label ?> (tháng/năm)<?= $reqMark ?></label>
        <div class="row g-1">
            <div class="col-5">
                <input type="number" name="<?= $section ?>[<?= $index ?>][<?= $role ?>_month]"
                    class="form-control form-control-sm" min="1" max="12" step="1"
                    placeholder="Tháng" inputmode="numeric"
                    value="<?= htmlspecialchars($parts['month']) ?>"
                    <?= $required ? 'required' : '' ?>>
            </div>
            <div class="col-7">
                <input type="number" name="<?= $section ?>[<?= $index ?>][<?= $role ?>_year]"
                    class="form-control form-control-sm" min="1950" max="2100" step="1"
                    placeholder="Năm" inputmode="numeric"
                    value="<?= htmlspecialchars($parts['year']) ?>"
                    <?= $required ? 'required' : '' ?>>
            </div>
        </div>
        <?php if ($role === 'end'): ?>
            <small class="text-muted">Để trống nếu đang học/làm</small>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * @param array<string, mixed> $row
 */
function cv_builder_education_row(int|string $index, array $row): void
{
    ?>
    <div class="cv-repeat-row border rounded p-3 mb-3 bg-light">
        <div class="row g-2">
            <?php cv_builder_month_year_fields($index, 'educations', 'start', $row, true); ?>
            <?php cv_builder_month_year_fields($index, 'educations', 'end', $row, false); ?>
            <div class="col-md-6">
                <label class="form-label small">Trường <span class="text-danger">*</span></label>
                <input type="text" name="educations[<?= $index ?>][school_name]" class="form-control form-control-sm" required
                    value="<?= htmlspecialchars((string) ($row['school_name'] ?? '')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label small">Ngành</label>
                <input type="text" name="educations[<?= $index ?>][major]" class="form-control form-control-sm"
                    value="<?= htmlspecialchars((string) ($row['major'] ?? '')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label small">Mô tả</label>
                <textarea name="educations[<?= $index ?>][description]" class="form-control form-control-sm" rows="2"><?= htmlspecialchars((string) ($row['description'] ?? '')) ?></textarea>
            </div>
            <div class="col-12 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger js-remove-row"><i class="fas fa-trash"></i> Xóa dòng</button>
            </div>
        </div>
    </div>
    <?php
}

/**
 * @param array<string, mixed> $row
 */
function cv_builder_experience_row(int|string $index, array $row): void
{
    ?>
    <div class="cv-repeat-row border rounded p-3 mb-3 bg-light">
        <div class="row g-2">
            <?php cv_builder_month_year_fields($index, 'experiences', 'start', $row, true); ?>
            <?php cv_builder_month_year_fields($index, 'experiences', 'end', $row, false); ?>
            <div class="col-md-6">
                <label class="form-label small">Công ty <span class="text-danger">*</span></label>
                <input type="text" name="experiences[<?= $index ?>][company_name]" class="form-control form-control-sm" required
                    value="<?= htmlspecialchars((string) ($row['company_name'] ?? '')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label small">Vị trí</label>
                <input type="text" name="experiences[<?= $index ?>][position]" class="form-control form-control-sm"
                    value="<?= htmlspecialchars((string) ($row['position'] ?? '')) ?>">
            </div>
            <div class="col-12">
                <label class="form-label small">Mô tả</label>
                <textarea name="experiences[<?= $index ?>][description]" class="form-control form-control-sm" rows="2"><?= htmlspecialchars((string) ($row['description'] ?? '')) ?></textarea>
            </div>
            <div class="col-12 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger js-remove-row"><i class="fas fa-trash"></i> Xóa dòng</button>
            </div>
        </div>
    </div>
    <?php
}

/**
 * @param array<string, mixed> $row
 */
function cv_builder_skill_row(int|string $index, array $row): void
{
    ?>
    <div class="cv-repeat-row border rounded p-3 mb-3 bg-light">
        <div class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small">Tên kỹ năng <span class="text-danger">*</span></label>
                <input type="text" name="skills[<?= $index ?>][skill_name]" class="form-control form-control-sm" required
                    value="<?= htmlspecialchars((string) ($row['skill_name'] ?? '')) ?>">
            </div>
            <div class="col-md-5">
                <label class="form-label small">Mô tả</label>
                <input type="text" name="skills[<?= $index ?>][description]" class="form-control form-control-sm"
                    value="<?= htmlspecialchars((string) ($row['description'] ?? '')) ?>">
            </div>
            <div class="col-md-2 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger js-remove-row w-100"><i class="fas fa-trash"></i> Xóa</button>
            </div>
        </div>
    </div>
    <?php
}

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="mb-4">
        <a href="cv-manage.php" class="text-success text-decoration-none"><i class="fas fa-arrow-left"></i> Quay lại quản lý CV</a>
        <h3 class="fw-bold mt-2"><?= htmlspecialchars($pageTitle) ?></h3>
    </div>

    <?php if (!$schemaReady): ?>
        <?= cvs_schema_migration_hint_html() ?>
    <?php else: ?>
        <form method="POST" class="card border-0 shadow-sm" enctype="multipart/form-data">
            <div class="card-body p-4 p-lg-5">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('candidate_cv_save_form')) ?>">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="cv_id" value="<?= $cvId ?>">
                <?php endif; ?>

                <div class="mb-4">
                    <label class="form-label fw-bold">Tên CV <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" required maxlength="255"
                        placeholder="VD: CV IT Fresher 2026"
                        value="<?= htmlspecialchars((string) ($profile['title'] ?? '')) ?>">
                </div>

                <h5 class="fw-bold border-bottom pb-2 mb-3">Thông tin cá nhân</h5>
                <div class="row g-3 mb-4 align-items-start">
                    <div class="col-md-3">
                        <div class="border rounded p-3 text-center bg-light cv-avatar-upload-box">
                            <div class="cv-avatar-preview-wrap mx-auto mb-2" style="width:140px;height:140px;">
                                <img id="cv-avatar-preview" src="<?= $avatarPreviewUrl
                                    ? htmlspecialchars($avatarPreviewUrl)
                                    : 'https://ui-avatars.com/api/?name=CV&size=140&background=e9ecef&color=6c757d' ?>"
                                    alt="Ảnh đại diện" class="rounded w-100 h-100" style="object-fit:cover;">
                            </div>
                            <input type="hidden" name="avatar_path" id="cv-avatar-path"
                                value="<?= htmlspecialchars((string) ($profile['avatar_path'] ?? '')) ?>">
                            <input type="hidden" name="remove_avatar" id="cv-remove-avatar" value="0">
                            <input type="file" name="avatar_file" id="cv-avatar-file" class="form-control form-control-sm mb-2"
                                accept="image/jpeg,image/png,image/webp">
                            <button type="button" class="btn btn-sm btn-outline-danger w-100" id="cv-avatar-remove-btn"
                                <?= $avatarPreviewUrl ? '' : 'disabled' ?>>
                                <i class="fas fa-trash"></i> Xóa ảnh
                            </button>
                            <p class="small text-muted mt-2 mb-0 text-start">
                                <strong>Quy chuẩn:</strong><br>
                                <?= htmlspecialchars((string) $avatarRules['type_label']) ?><br>
                                Tối đa 2MB<br>
                                <?= (int) $avatarRules['min_width'] ?>×<?= (int) $avatarRules['min_height'] ?>
                                – <?= (int) $avatarRules['max_width'] ?>×<?= (int) $avatarRules['max_height'] ?> px<br>
                                Ảnh chân dung gần vuông
                            </p>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Họ tên <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" required
                            value="<?= htmlspecialchars((string) ($profile['full_name'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Vị trí ứng tuyển <span class="text-danger">*</span></label>
                        <input type="text" name="target_position" class="form-control" required
                            value="<?= htmlspecialchars((string) ($profile['target_position'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required
                            value="<?= htmlspecialchars((string) ($profile['email'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                        <input type="tel" name="phone" class="form-control" required maxlength="10"
                            pattern="0[0-9]{9}" inputmode="numeric" autocomplete="tel"
                            placeholder="0912345678" title="10 chữ số, bắt đầu bằng 0"
                            value="<?= htmlspecialchars((string) ($profile['phone'] ?? '')) ?>">
                        <small class="text-muted">Bắt đầu bằng 0, đủ 10 chữ số</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Ngày sinh</label>
                        <input type="date" name="date_of_birth" class="form-control"
                            min="<?= htmlspecialchars($dobBounds['min']) ?>"
                            max="<?= htmlspecialchars($dobBounds['max']) ?>"
                            value="<?= htmlspecialchars((string) ($profile['date_of_birth'] ?? '')) ?>">
                        <small class="text-muted">Không quá <?= (int) $dobBounds['max_age_years'] ?> tuổi, không ở tương lai</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Giới tính <span class="text-danger">*</span></label>
                        <select name="gender" class="form-select" required>
                            <?php $currentGender = (string) ($profile['gender'] ?? ''); ?>
                            <option value="" disabled <?= $currentGender === '' ? 'selected' : '' ?>>— Chọn —</option>
                            <?php foreach (cv_allowed_genders() as $genderOption): ?>
                                <option value="<?= htmlspecialchars($genderOption) ?>"
                                    <?= $currentGender === $genderOption ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($genderOption) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Website</label>
                        <input type="url" name="website" class="form-control" placeholder="https://"
                            value="<?= htmlspecialchars((string) ($profile['website'] ?? '')) ?>">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Địa chỉ</label>
                        <input type="text" name="address" class="form-control"
                            value="<?= htmlspecialchars((string) ($profile['address'] ?? '')) ?>">
                    </div>
                        </div>
                    </div>
                </div>

                <h5 class="fw-bold border-bottom pb-2 mb-3">Mục tiêu nghề nghiệp</h5>
                <div class="mb-4">
                    <textarea name="career_objective" class="form-control" rows="4"
                        placeholder="Mô tả ngắn mục tiêu nghề nghiệp..."><?= htmlspecialchars((string) ($profile['career_objective'] ?? '')) ?></textarea>
                </div>

                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h5 class="fw-bold mb-0">Học vấn</h5>
                    <button type="button" class="btn btn-sm btn-outline-success" data-add-row="education-rows">
                        <i class="fas fa-plus"></i> Thêm học vấn
                    </button>
                </div>
                <div id="education-rows" class="mb-4">
                    <?php foreach ($educations as $i => $row): ?>
                        <?php cv_builder_education_row((int) $i, $row); ?>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h5 class="fw-bold mb-0">Kinh nghiệm</h5>
                    <button type="button" class="btn btn-sm btn-outline-success" data-add-row="experience-rows">
                        <i class="fas fa-plus"></i> Thêm kinh nghiệm
                    </button>
                </div>
                <div id="experience-rows" class="mb-4">
                    <?php foreach ($experiences as $i => $row): ?>
                        <?php cv_builder_experience_row((int) $i, $row); ?>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h5 class="fw-bold mb-0">Kỹ năng</h5>
                    <button type="button" class="btn btn-sm btn-outline-success" data-add-row="skill-rows">
                        <i class="fas fa-plus"></i> Thêm kỹ năng
                    </button>
                </div>
                <div id="skill-rows" class="mb-4">
                    <?php foreach ($skills as $i => $row): ?>
                        <?php cv_builder_skill_row((int) $i, $row); ?>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex flex-wrap gap-2 pt-3 border-top">
                    <button type="submit" class="btn btn-success fw-bold px-4">Lưu CV</button>
                    <a href="cv-manage.php" class="btn btn-outline-secondary">Hủy</a>
                </div>
            </div>
        </form>

        <script type="text/template" id="tpl-education-row"><?php cv_builder_education_row('__INDEX__', []); ?></script>
        <script type="text/template" id="tpl-experience-row"><?php cv_builder_experience_row('__INDEX__', []); ?></script>
        <script type="text/template" id="tpl-skill-row"><?php cv_builder_skill_row('__INDEX__', []); ?></script>

        <script src="<?= BASE_URL ?>assets/js/cv-builder.js"></script>
        <script src="<?= BASE_URL ?>assets/js/cv-builder-avatar.js"></script>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
