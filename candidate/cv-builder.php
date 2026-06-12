<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/schema_cvs.php';
require_once __DIR__ . '/../includes/cv_rules.php';
require_once __DIR__ . '/../includes/cv_import_rules.php';
require_once __DIR__ . '/../includes/cv_import_pdf_quality.php';
require_once __DIR__ . '/../includes/cv_avatar.php';
require_once __DIR__ . '/../includes/services/CvService.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'candidate') {
    header('Location: ../index.php');
    exit();
}

$userId = (int) $_SESSION['user_id'];
$cvId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $cvId > 0;
$fromImport = !$isEdit
    && isset($_GET['from_import'])
    && (string) $_GET['from_import'] === '1';
$requestedTemplate = null;
if (!$isEdit && isset($_GET['template']) && trim((string) $_GET['template']) !== '') {
    $requestedTemplate = cv_normalize_template_key((string) $_GET['template']);
}
$schemaReady = cvs_schema_ready($conn);
$importAttachmentPath = '';
$importMeta = ['parse_source' => '', 'warnings' => []];

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
    'interests' => '',
    'template_key' => 'classic',
    'avatar_path' => '',
];
$educations = [];
$experiences = [];
$skills = [];
$activities = [];
$certificates = [];
$awards = [];
$references = [];
$projects = [];
$extendedReady = $schemaReady && cvs_extended_sections_ready($conn);
$projectsReady = $schemaReady && cvs_projects_ready($conn);

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
    $parsed['profile']['attachment_path'] = cv_import_validate_attachment_path(
        (string) ($parsed['profile']['attachment_path'] ?? ''),
        $userId
    );

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
        unset($_SESSION['cv_import_draft']);
        header('Location: cv-manage.php');
        exit();
    }

    $profile = $parsed['profile'];
    $educations = $parsed['children']['educations'];
    $experiences = $parsed['children']['experiences'];
    $skills = $parsed['children']['skills'];
    $activities = $parsed['children']['activities'];
    $certificates = $parsed['children']['certificates'];
    $awards = $parsed['children']['awards'];
    $references = $parsed['children']['references'];
    $projects = $parsed['children']['projects'];
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
    $activities = $loaded['data']['activities'] ?? [];
    $certificates = $loaded['data']['certificates'] ?? [];
    $awards = $loaded['data']['awards'] ?? [];
    $references = $loaded['data']['references'] ?? [];
    $projects = $loaded['data']['projects'] ?? [];
    $profile['phone'] = cv_normalize_phone((string) ($profile['phone'] ?? ''));
    if (empty($profile['template_key'])) {
        $profile['template_key'] = 'classic';
    }
} elseif ($schemaReady && $fromImport && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $draft = $_SESSION['cv_import_draft'] ?? null;
    $draftUserId = is_array($draft) ? (int) ($draft['user_id'] ?? 0) : 0;

    if (!is_array($draft) || $draftUserId !== $userId) {
        if (is_array($draft)) {
            unset($_SESSION['cv_import_draft']);
        }
        $_SESSION['swal_icon'] = 'warning';
        $_SESSION['swal_title'] = $draftUserId > 0 && $draftUserId !== $userId
            ? 'Phiên import không hợp lệ. Vui lòng upload PDF lại.'
            : 'Không có dữ liệu import. Vui lòng upload PDF lại.';
        header('Location: ' . ($draftUserId > 0 && $draftUserId !== $userId ? 'cv-manage.php' : 'cv-import.php'));
        exit();
    }

    $draftProfile = is_array($draft['profile'] ?? null) ? $draft['profile'] : [];
    $draftChildren = is_array($draft['children'] ?? null) ? $draft['children'] : [];
    $importMeta = is_array($draft['meta'] ?? null) ? $draft['meta'] : ['parse_source' => '', 'warnings' => []];
    $importAttachmentPath = trim((string) ($draft['attachment_path'] ?? ''));

    foreach ([
        'title', 'full_name', 'target_position', 'date_of_birth', 'gender',
        'phone', 'email', 'website', 'address', 'career_objective', 'interests', 'template_key',
    ] as $key) {
        $val = trim((string) ($draftProfile[$key] ?? ''));
        if ($val !== '') {
            $profile[$key] = $draftProfile[$key];
        }
    }
    $profile['phone'] = cv_normalize_phone((string) ($profile['phone'] ?? ''));
    $profile['template_key'] = cv_builder_resolve_initial_template_key(
        $isEdit,
        $requestedTemplate,
        $profile['template_key'] ?? null
    );

    $stmt = $conn->prepare('SELECT fullname, email, phone FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $userRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($userRow) {
        if (trim((string) ($profile['full_name'] ?? '')) === '') {
            $profile['full_name'] = (string) ($userRow['fullname'] ?? '');
        }
        if (trim((string) ($profile['email'] ?? '')) === '') {
            $profile['email'] = (string) ($userRow['email'] ?? '');
        }
        if (trim((string) ($profile['phone'] ?? '')) === '') {
            $prefillPhone = cv_normalize_phone((string) ($userRow['phone'] ?? ''));
            if (cv_is_valid_phone_vn($prefillPhone)) {
                $profile['phone'] = $prefillPhone;
            }
        }
    }

    $educations = is_array($draftChildren['educations'] ?? null) ? $draftChildren['educations'] : [];
    $experiences = is_array($draftChildren['experiences'] ?? null) ? $draftChildren['experiences'] : [];
    $skills = is_array($draftChildren['skills'] ?? null) ? $draftChildren['skills'] : [];
    $activities = is_array($draftChildren['activities'] ?? null) ? $draftChildren['activities'] : [];
    $certificates = is_array($draftChildren['certificates'] ?? null) ? $draftChildren['certificates'] : [];
    $awards = is_array($draftChildren['awards'] ?? null) ? $draftChildren['awards'] : [];
    $references = is_array($draftChildren['references'] ?? null) ? $draftChildren['references'] : [];
    $projects = is_array($draftChildren['projects'] ?? null) ? $draftChildren['projects'] : [];
} elseif ($schemaReady && !$isEdit && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $profile['template_key'] = cv_builder_resolve_initial_template_key(
        $isEdit,
        $requestedTemplate,
        $profile['template_key'] ?? null
    );
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

$pageTitle = $isEdit ? 'Sửa CV online' : ($fromImport ? 'Tạo CV từ PDF' : 'Tạo CV mới');

/**
 * @param array{parse_source?: string, parse_mode?: string, warnings?: list<string>} $meta
 */
function cv_builder_import_source_label(array $meta): string
{
    $parseMode = (string) ($meta['parse_mode'] ?? '');
    if ($parseMode !== '') {
        return match ($parseMode) {
            'text_fast' => 'Text-base (Groq)',
            'vision_gpt', 'vision_gpt_forced' => 'Chuẩn GPT (vision)',
            default => cv_import_parse_mode_label($parseMode),
        };
    }

    $source = (string) ($meta['parse_source'] ?? '');
    return match ($source) {
        'ai' => 'Text-base (Groq)',
        'vision' => 'Chuẩn GPT (vision)',
        'fallback' => 'phân tích cơ bản (fallback)',
        'ai+fallback' => 'AI + bổ sung fallback',
        default => 'import PDF',
    };
}

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
function cv_builder_project_row(int|string $index, array $row): void
{
    ?>
    <div class="cv-repeat-row border rounded p-3 mb-3 bg-light">
        <div class="row g-2">
            <?php cv_builder_month_year_fields($index, 'projects', 'start', $row, true); ?>
            <?php cv_builder_month_year_fields($index, 'projects', 'end', $row, false); ?>
            <div class="col-md-6">
                <label class="form-label small">Tên dự án <span class="text-danger">*</span></label>
                <input type="text" name="projects[<?= $index ?>][project_name]" class="form-control form-control-sm" required
                    value="<?= htmlspecialchars((string) ($row['project_name'] ?? '')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label small">Vị trí trong dự án</label>
                <input type="text" name="projects[<?= $index ?>][position]" class="form-control form-control-sm"
                    placeholder="VD: Frontend Developer"
                    value="<?= htmlspecialchars((string) ($row['position'] ?? '')) ?>">
            </div>
            <div class="col-12">
                <label class="form-label small">Mô tả</label>
                <textarea name="projects[<?= $index ?>][description]" class="form-control form-control-sm" rows="3"
                    placeholder="Mục tiêu dự án, vai trò, công nghệ, kết quả đạt được..."><?= htmlspecialchars((string) ($row['description'] ?? '')) ?></textarea>
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
function cv_builder_single_month_year_fields(
    int|string $index,
    string $section,
    string $prefix,
    array $row,
    string $label = 'Thời gian'
): void {
    $parts = cv_split_year_month($row[$prefix . '_at'] ?? null);
    ?>
    <div class="col-md-3">
        <label class="form-label small"><?= htmlspecialchars($label) ?> (tháng/năm)</label>
        <div class="row g-1">
            <div class="col-5">
                <input type="number" name="<?= $section ?>[<?= $index ?>][<?= $prefix ?>_month]"
                    class="form-control form-control-sm" min="1" max="12" step="1"
                    placeholder="Tháng" inputmode="numeric"
                    value="<?= htmlspecialchars($parts['month']) ?>">
            </div>
            <div class="col-7">
                <input type="number" name="<?= $section ?>[<?= $index ?>][<?= $prefix ?>_year]"
                    class="form-control form-control-sm" min="1950" max="2100" step="1"
                    placeholder="Năm" inputmode="numeric"
                    value="<?= htmlspecialchars($parts['year']) ?>">
            </div>
        </div>
    </div>
    <?php
}

/**
 * @param array<string, mixed> $row
 */
function cv_builder_activity_row(int|string $index, array $row): void
{
    ?>
    <div class="cv-repeat-row border rounded p-3 mb-3 bg-light">
        <div class="row g-2">
            <?php cv_builder_month_year_fields($index, 'activities', 'start', $row, true); ?>
            <?php cv_builder_month_year_fields($index, 'activities', 'end', $row, false); ?>
            <div class="col-md-6">
                <label class="form-label small">Tổ chức / hoạt động <span class="text-danger">*</span></label>
                <input type="text" name="activities[<?= $index ?>][organization]" class="form-control form-control-sm" required
                    value="<?= htmlspecialchars((string) ($row['organization'] ?? '')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label small">Vai trò</label>
                <input type="text" name="activities[<?= $index ?>][role]" class="form-control form-control-sm"
                    value="<?= htmlspecialchars((string) ($row['role'] ?? '')) ?>">
            </div>
            <div class="col-12">
                <label class="form-label small">Mô tả</label>
                <textarea name="activities[<?= $index ?>][description]" class="form-control form-control-sm" rows="2"><?= htmlspecialchars((string) ($row['description'] ?? '')) ?></textarea>
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
function cv_builder_certificate_row(int|string $index, array $row): void
{
    ?>
    <div class="cv-repeat-row border rounded p-3 mb-3 bg-light">
        <div class="row g-2 align-items-end">
            <?php cv_builder_single_month_year_fields($index, 'certificates', 'issued', $row, 'Ngày cấp'); ?>
            <div class="col-md-6">
                <label class="form-label small">Tên chứng chỉ <span class="text-danger">*</span></label>
                <input type="text" name="certificates[<?= $index ?>][certificate_name]" class="form-control form-control-sm" required
                    value="<?= htmlspecialchars((string) ($row['certificate_name'] ?? '')) ?>">
            </div>
            <div class="col-md-3 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger js-remove-row w-100"><i class="fas fa-trash"></i> Xóa</button>
            </div>
        </div>
    </div>
    <?php
}

/**
 * @param array<string, mixed> $row
 */
function cv_builder_award_row(int|string $index, array $row): void
{
    ?>
    <div class="cv-repeat-row border rounded p-3 mb-3 bg-light">
        <div class="row g-2">
            <?php cv_builder_single_month_year_fields($index, 'awards', 'awarded', $row, 'Thời gian'); ?>
            <div class="col-md-6">
                <label class="form-label small">Tên giải thưởng <span class="text-danger">*</span></label>
                <input type="text" name="awards[<?= $index ?>][title]" class="form-control form-control-sm" required
                    value="<?= htmlspecialchars((string) ($row['title'] ?? '')) ?>">
            </div>
            <div class="col-12">
                <label class="form-label small">Mô tả</label>
                <textarea name="awards[<?= $index ?>][description]" class="form-control form-control-sm" rows="2"><?= htmlspecialchars((string) ($row['description'] ?? '')) ?></textarea>
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
function cv_builder_reference_row(int|string $index, array $row): void
{
    ?>
    <div class="cv-repeat-row border rounded p-3 mb-3 bg-light">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">Họ tên <span class="text-danger">*</span></label>
                <input type="text" name="references[<?= $index ?>][full_name]" class="form-control form-control-sm" required
                    value="<?= htmlspecialchars((string) ($row['full_name'] ?? '')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Chức vụ</label>
                <input type="text" name="references[<?= $index ?>][position]" class="form-control form-control-sm"
                    value="<?= htmlspecialchars((string) ($row['position'] ?? '')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Liên hệ</label>
                <input type="text" name="references[<?= $index ?>][contact_info]" class="form-control form-control-sm"
                    value="<?= htmlspecialchars((string) ($row['contact_info'] ?? '')) ?>">
            </div>
            <div class="col-md-2 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger js-remove-row w-100"><i class="fas fa-trash"></i> Xóa</button>
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
    <?php elseif (!$extendedReady): ?>
        <?= cvs_extended_migration_hint_html() ?>
    <?php else: ?>
        <?php if ($fromImport && $importAttachmentPath !== ''): ?>
            <?php
            $importWarnings = is_array($importMeta['warnings'] ?? null) ? $importMeta['warnings'] : [];
            $importPdfUrl = BASE_URL . ltrim($importAttachmentPath, '/');
            ?>
            <div class="alert alert-info border-0 shadow-sm mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                    <div>
                        <strong><i class="fas fa-file-pdf"></i> Đã nhập từ PDF</strong>
                        — vui lòng kiểm tra và chỉnh sửa trước khi lưu.
                        <br><small class="text-muted">Nguồn phân tích: <?= htmlspecialchars(cv_builder_import_source_label($importMeta)) ?></small>
                        <?php if ($importWarnings !== []): ?>
                            <ul class="small mb-0 mt-2 ps-3">
                                <?php foreach ($importWarnings as $warning): ?>
                                    <li><?= htmlspecialchars((string) $warning) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    <a href="<?= htmlspecialchars($importPdfUrl) ?>" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">
                        <i class="fas fa-external-link-alt"></i> Xem file PDF gốc
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" class="card border-0 shadow-sm" enctype="multipart/form-data">
            <div class="card-body p-4 p-lg-5">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('candidate_cv_save_form')) ?>">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="cv_id" value="<?= $cvId ?>">
                <?php endif; ?>
                <?php if ($fromImport && $importAttachmentPath !== ''): ?>
                    <input type="hidden" name="attachment_path" value="<?= htmlspecialchars($importAttachmentPath) ?>">
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

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Mẫu CV (xem trước)</label>
                        <?php $currentTemplate = cv_normalize_template_key((string) ($profile['template_key'] ?? 'classic')); ?>
                        <select name="template_key" class="form-select">
                            <option value="classic" <?= $currentTemplate === 'classic' ? 'selected' : '' ?>>Classic — tiêu đề xanh, bố cục dọc</option>
                            <option value="modern" <?= $currentTemplate === 'modern' ? 'selected' : '' ?>>Modern — sidebar xanh, nội dung trắng</option>
                        </select>
                        <?php if (!$isEdit && $requestedTemplate !== null): ?>
                            <small class="text-muted">Đã chọn mẫu ở bước trước — bạn vẫn có thể đổi tại đây.</small>
                        <?php endif; ?>
                    </div>
                </div>

                <h5 class="fw-bold border-bottom pb-2 mb-3">Mục tiêu nghề nghiệp</h5>
                <div class="mb-4">
                    <textarea name="career_objective" class="form-control" rows="4"
                        placeholder="Mô tả ngắn mục tiêu nghề nghiệp..."><?= htmlspecialchars((string) ($profile['career_objective'] ?? '')) ?></textarea>
                </div>

                <h5 class="fw-bold border-bottom pb-2 mb-3">Sở thích</h5>
                <div class="mb-4">
                    <textarea name="interests" class="form-control" rows="3"
                        placeholder="Sở thích, đam mê (tùy chọn)..."><?= htmlspecialchars((string) ($profile['interests'] ?? '')) ?></textarea>
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

                <?php if (!$projectsReady): ?>
                    <?= cvs_projects_migration_hint_html() ?>
                <?php else: ?>
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h5 class="fw-bold mb-0">Dự án</h5>
                    <button type="button" class="btn btn-sm btn-outline-success" data-add-row="project-rows">
                        <i class="fas fa-plus"></i> Thêm dự án
                    </button>
                </div>
                <div id="project-rows" class="mb-4">
                    <?php foreach ($projects as $i => $row): ?>
                        <?php cv_builder_project_row((int) $i, $row); ?>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

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

                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h5 class="fw-bold mb-0">Hoạt động</h5>
                    <button type="button" class="btn btn-sm btn-outline-success" data-add-row="activity-rows">
                        <i class="fas fa-plus"></i> Thêm hoạt động
                    </button>
                </div>
                <div id="activity-rows" class="mb-4">
                    <?php foreach ($activities as $i => $row): ?>
                        <?php cv_builder_activity_row((int) $i, $row); ?>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h5 class="fw-bold mb-0">Chứng chỉ</h5>
                    <button type="button" class="btn btn-sm btn-outline-success" data-add-row="certificate-rows">
                        <i class="fas fa-plus"></i> Thêm chứng chỉ
                    </button>
                </div>
                <div id="certificate-rows" class="mb-4">
                    <?php foreach ($certificates as $i => $row): ?>
                        <?php cv_builder_certificate_row((int) $i, $row); ?>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h5 class="fw-bold mb-0">Giải thưởng</h5>
                    <button type="button" class="btn btn-sm btn-outline-success" data-add-row="award-rows">
                        <i class="fas fa-plus"></i> Thêm giải thưởng
                    </button>
                </div>
                <div id="award-rows" class="mb-4">
                    <?php foreach ($awards as $i => $row): ?>
                        <?php cv_builder_award_row((int) $i, $row); ?>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h5 class="fw-bold mb-0">Người giới thiệu</h5>
                    <button type="button" class="btn btn-sm btn-outline-success" data-add-row="reference-rows">
                        <i class="fas fa-plus"></i> Thêm người giới thiệu
                    </button>
                </div>
                <div id="reference-rows" class="mb-4">
                    <?php foreach ($references as $i => $row): ?>
                        <?php cv_builder_reference_row((int) $i, $row); ?>
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
        <script type="text/template" id="tpl-project-row"><?php cv_builder_project_row('__INDEX__', []); ?></script>
        <script type="text/template" id="tpl-skill-row"><?php cv_builder_skill_row('__INDEX__', []); ?></script>
        <script type="text/template" id="tpl-activity-row"><?php cv_builder_activity_row('__INDEX__', []); ?></script>
        <script type="text/template" id="tpl-certificate-row"><?php cv_builder_certificate_row('__INDEX__', []); ?></script>
        <script type="text/template" id="tpl-award-row"><?php cv_builder_award_row('__INDEX__', []); ?></script>
        <script type="text/template" id="tpl-reference-row"><?php cv_builder_reference_row('__INDEX__', []); ?></script>

        <script src="<?= BASE_URL ?>assets/js/cv-builder.js"></script>
        <script src="<?= BASE_URL ?>assets/js/cv-builder-avatar.js"></script>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
