<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/schema_cvs.php';
require_once __DIR__ . '/../includes/cv_rules.php';
require_once __DIR__ . '/../includes/cv_template_catalog.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'candidate') {
    header('Location: ../index.php');
    exit();
}

$userId = (int) $_SESSION['user_id'];
$schemaReady = cvs_schema_ready($conn);
$fromImport = isset($_GET['from_import']) && (string) $_GET['from_import'] === '1';

if ($schemaReady && $fromImport) {
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
}

$templates = cv_template_catalog_list();
$builderQuery = $fromImport ? ['from_import' => '1'] : [];

include '../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/cv-preview.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/cv-template-picker.css">

<div class="container py-5">
    <div class="mb-4">
        <a href="<?= $fromImport ? 'cv-import.php' : 'cv-manage.php' ?>" class="text-success text-decoration-none">
            <i class="fas fa-arrow-left"></i> <?= $fromImport ? 'Import PDF' : 'Quản lý CV' ?>
        </a>
        <h3 class="fw-bold mt-2 mb-1"><i class="fas fa-palette text-success"></i> Chọn mẫu CV</h3>
        <p class="text-muted mb-0">
            <?php if ($fromImport): ?>
                PDF đã được phân tích — chọn mẫu hiển thị trước khi chỉnh sửa nội dung.
            <?php else: ?>
                Chọn mẫu phù hợp trước khi nhập thông tin. Bạn vẫn có thể đổi mẫu trong bước chỉnh sửa.
            <?php endif; ?>
        </p>
    </div>

    <?php if (!$schemaReady): ?>
        <?= cvs_schema_migration_hint_html() ?>
    <?php else: ?>
        <div class="row g-4 justify-content-center cv-template-gallery">
            <?php foreach ($templates as $tpl): ?>
                <?php include __DIR__ . '/../includes/partials/cv_template_card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
