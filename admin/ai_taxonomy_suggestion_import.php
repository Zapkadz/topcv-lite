<?php
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/schema_ai_taxonomy.php';
require_once __DIR__ . '/../includes/ai_taxonomy_config.php';
require_once __DIR__ . '/../includes/services/AiTaxonomyService.php';
include 'includes/header.php';

if (!ai_taxonomy_schema_ready($conn)) {
    echo ai_taxonomy_migration_hint_html();
    include 'includes/footer.php';
    exit;
}

$adminId = (int) ($_SESSION['user_id'] ?? 0);
$cfg = ai_taxonomy_config();
$queuePath = trim((string) ($cfg['suggestion_queue_path'] ?? ''));
$queueExists = $queuePath !== '' && is_file($queuePath);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate('admin_taxonomy_import_form', $_POST['csrf_token'] ?? '')) {
        $_SESSION['swal_icon'] = 'error';
        $_SESSION['swal_title'] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
        header('Location: ai_taxonomy_suggestion_import.php');
        exit();
    }

    $mode = (string) ($_POST['import_mode'] ?? '');
    if ($mode === 'configured_path') {
        $result = AiTaxonomyService::importFromPath($conn, $queuePath, $adminId);
    } elseif ($mode === 'upload' && isset($_FILES['json_file'])) {
        $result = AiTaxonomyService::importFromUpload($conn, $_FILES['json_file'], $adminId);
    } else {
        $result = ['ok' => false, 'message' => 'Chọn phương thức import hợp lệ.'];
    }

    $_SESSION['swal_icon'] = $result['ok'] ? 'success' : 'error';
    $_SESSION['swal_title'] = $result['message'];
    header('Location: ai_taxonomy_suggestions.php');
    exit();
}
?>

<div class="mb-4">
    <a href="ai_taxonomy_suggestions.php" class="text-decoration-none small"><i class="fas fa-arrow-left"></i> Danh sách suggestions</a>
    <h3 class="mt-2 fw-bold text-success"><i class="fas fa-file-import"></i> Import taxonomy suggestions</h3>
    <p class="text-muted small">Đọc queue JSON từ AI Python Phase 15. Suggestion đã duyệt sẽ không bị ghi đè.</p>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <h5 class="fw-bold">1. Đọc file cấu hình</h5>
                <p class="small text-muted">Đường dẫn cố định trong <code>config/ai_taxonomy.local.php</code> (mẫu: <code>ai_taxonomy.example.php</code>).</p>
                <div class="bg-light rounded p-3 small mb-3">
                    <code class="d-block text-break"><?= htmlspecialchars($queuePath ?: '(chưa cấu hình)') ?></code>
                    <?php if ($queueExists): ?>
                        <span class="badge bg-success mt-2">File tồn tại</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark mt-2">File chưa có — chạy taxonomy_suggest.py trước</span>
                    <?php endif; ?>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('admin_taxonomy_import_form')) ?>">
                    <input type="hidden" name="import_mode" value="configured_path">
                    <button type="submit" class="btn btn-success" <?= $queueExists ? '' : 'disabled' ?>>Import từ đường dẫn cấu hình</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <h5 class="fw-bold">2. Upload file JSON</h5>
                <p class="small text-muted">File phải có <code>version</code> và mảng <code>suggestions</code> (tối đa 5MB).</p>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('admin_taxonomy_import_form')) ?>">
                    <input type="hidden" name="import_mode" value="upload">
                    <div class="mb-3">
                        <input type="file" name="json_file" class="form-control" accept=".json,application/json" required>
                    </div>
                    <button type="submit" class="btn btn-outline-success">Upload &amp; Import</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info border-0 shadow-sm mt-4 small mb-0">
    <strong>CLI tạo queue (AI Python):</strong>
    <code class="d-block mt-1">python taxonomy_suggest.py --input-json outputs/ranking_results.json --output-json outputs/taxonomy_suggestions.json</code>
</div>

<?php include 'includes/footer.php'; ?>
