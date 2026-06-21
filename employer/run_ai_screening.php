<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/employer_screening_rules.php';
require_once __DIR__ . '/../includes/services/ApplicationService.php';
require_once __DIR__ . '/../includes/services/AiScreeningService.php';
include 'auth_check.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
$jobId = isset($_POST['job_id']) ? (int) $_POST['job_id'] : 0;
$redirectUrl = $jobId > 0 ? 'job_candidates.php?job_id=' . $jobId : 'candidate_screening.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirectUrl);
    exit();
}

if (!csrf_validate('employer_run_ai_screening_form', $_POST['csrf_token'] ?? '')) {
    $_SESSION['swal_icon'] = 'error';
    $_SESSION['swal_title'] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
    header('Location: ' . $redirectUrl);
    exit();
}

if ($jobId <= 0) {
    $_SESSION['swal_icon'] = 'warning';
    $_SESSION['swal_title'] = 'Thiếu mã tin tuyển dụng.';
    header('Location: candidate_screening.php');
    exit();
}

$stmt = $conn->prepare('SELECT id FROM companies WHERE user_id = ? LIMIT 1');
$stmt->execute([$userId]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    header('Location: company.php');
    exit();
}

$companyId = (int) $company['id'];
$result = AiScreeningService::runForJob($conn, $jobId, $companyId);

$_SESSION['swal_icon'] = $result['ok'] ? 'success' : 'error';
$_SESSION['swal_title'] = $result['message'];
if (!empty($result['detail'])) {
    $_SESSION['swal_text'] = (string) $result['detail'];
}
if (!$result['ok']) {
    $_SESSION['swal_persistent'] = true;
} elseif (!empty($result['skipped_count'])) {
    $_SESSION['swal_text'] = (string) $result['skipped_count'] . ' UV bỏ qua vì thiếu CV text.';
}

if (!empty($result['trace_id']) || is_array($result['diagnostics'] ?? null)) {
    employer_screening_save_diag_flash($jobId, [
        'trace_id' => (string) ($result['trace_id'] ?? ''),
        'diagnostics' => is_array($result['diagnostics'] ?? null) ? $result['diagnostics'] : [],
        'ran_at' => time(),
    ]);
}

header('Location: ' . $redirectUrl);
exit();
