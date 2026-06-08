<?php
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/schema_ai_taxonomy.php';
require_once __DIR__ . '/../includes/services/AiTaxonomyService.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ai_taxonomy_suggestions.php');
    exit();
}

if (!csrf_validate('admin_taxonomy_export_form', $_POST['csrf_token'] ?? '')) {
    $_SESSION['swal_icon'] = 'error';
    $_SESSION['swal_title'] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
    header('Location: ai_taxonomy_suggestions.php');
    exit();
}

require_once __DIR__ . '/../config/db.php';

if (!ai_taxonomy_schema_ready($conn)) {
    $_SESSION['swal_icon'] = 'error';
    $_SESSION['swal_title'] = 'Chưa có schema taxonomy. Chạy migration trước.';
    header('Location: ai_taxonomy_suggestions.php');
    exit();
}

$adminId = (int) $_SESSION['user_id'];
$result = AiTaxonomyService::exportMergedTaxonomy($conn, $adminId);

$_SESSION['swal_icon'] = $result['ok'] ? 'success' : 'error';
$_SESSION['swal_title'] = $result['message'];
header('Location: ai_taxonomy_suggestions.php');
exit();
