<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/schema_cvs.php';
require_once __DIR__ . '/../includes/cv_rules.php';
require_once __DIR__ . '/../includes/cv_preview_render.php';
require_once __DIR__ . '/../includes/services/CvService.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'candidate') {
    header('Location: ../index.php');
    exit();
}

$userId = (int) $_SESSION['user_id'];
$cvId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$schemaReady = cvs_schema_ready($conn);
$previewHtml = '';
$cvTitle = '';
$templateKey = 'classic';
$loaded = ['ok' => false, 'data' => null];

if ($schemaReady && $cvId > 0) {
    $loaded = CvService::getFullForUser($conn, $userId, $cvId);
    if ($loaded['ok'] && $loaded['data']) {
        $previewHtml = cv_render_preview_html($loaded['data']);
        $cvTitle = (string) ($loaded['data']['profile']['title'] ?? 'CV');
        $templateKey = cv_normalize_template_key((string) ($loaded['data']['profile']['template_key'] ?? 'classic'));
    } else {
        $_SESSION['swal_icon'] = 'error';
        $_SESSION['swal_title'] = $loaded['message'] ?: 'Không tìm thấy CV.';
        header('Location: cv-manage.php');
        exit();
    }
} elseif ($schemaReady) {
    header('Location: cv-manage.php');
    exit();
}

include '../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/cv-preview.css">

<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <a href="cv-manage.php" class="text-success text-decoration-none"><i class="fas fa-arrow-left"></i> Quản lý CV</a>
            <h3 class="fw-bold mt-2 mb-0">Xem trước: <?= htmlspecialchars($cvTitle) ?></h3>
            <?php if ($cvId > 0): ?>
                <span class="badge bg-secondary"><?= $templateKey === 'modern' ? 'Mẫu Modern' : 'Mẫu Classic' ?></span>
            <?php endif; ?>
        </div>
        <?php if ($cvId > 0): ?>
            <div class="d-flex gap-2">
                <a href="cv-builder.php?id=<?= $cvId ?>" class="btn btn-outline-success btn-sm fw-bold">Sửa CV</a>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                    <i class="fas fa-print"></i> In
                </button>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!$schemaReady): ?>
        <?= cvs_schema_migration_hint_html() ?>
    <?php else: ?>
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <?= $previewHtml ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.cv-preview-contact .text-break {
    word-break: break-word;
    overflow-wrap: anywhere;
}
@media print {
    .navbar, footer, .btn, a.text-success { display: none !important; }
    body { background: #fff !important; }
    .cv-preview-classic,
    .cv-preview-modern { border: none !important; box-shadow: none !important; }
}
</style>

<?php include '../includes/footer.php'; ?>
