<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/services/SavedJobService.php';

$redirect = '../job-detail.php?id=' . (int) ($_POST['job_id'] ?? $_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || !isset($_SESSION['user_id'])
    || ($_SESSION['role'] ?? '') !== 'candidate') {
    $_SESSION['swal_icon'] = 'error';
    $_SESSION['swal_title'] = 'Bạn cần đăng nhập với tài khoản ứng viên.';
    header('Location: ' . $redirect);
    exit;
}

if (!csrf_validate('candidate_save_job_form', $_POST['csrf_token'] ?? '')) {
    $_SESSION['swal_icon'] = 'error';
    $_SESSION['swal_title'] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
    header('Location: ' . $redirect);
    exit;
}

$jobId = (int) ($_POST['job_id'] ?? 0);
$redirect = '../job-detail.php?id=' . $jobId;

$result = SavedJobService::toggle($conn, (int) $_SESSION['user_id'], $jobId);

$_SESSION['swal_icon'] = $result['ok'] ? 'success' : 'error';
$_SESSION['swal_title'] = $result['message'];

header('Location: ' . $redirect);
exit;
