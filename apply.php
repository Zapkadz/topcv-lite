<?php
session_start();
include 'config/db.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/services/ApplicationService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

if (isset($_SESSION['role']) && $_SESSION['role'] === 'employer') {
    $_SESSION['swal_icon'] = 'error';
    $_SESSION['swal_title'] = 'Nhà tuyển dụng không thể ứng tuyển!';
    header('Location: index.php');
    exit();
}

$userId = (int) $_SESSION['user_id'];
$jobId = isset($_POST['job_id']) ? (int) $_POST['job_id'] : 0;
$cvProfileId = isset($_POST['cv_profile_id']) ? (int) $_POST['cv_profile_id'] : 0;
$coverLetter = trim((string) ($_POST['cover_letter'] ?? ''));

if ($jobId <= 0 || !csrf_validate('apply_job_form', $_POST['csrf_token'] ?? '')) {
    $_SESSION['swal_icon'] = 'error';
    $_SESSION['swal_title'] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
    header('Location: index.php');
    exit();
}

$result = ApplicationService::applyToJob($conn, $userId, $jobId, $cvProfileId, $coverLetter);

$_SESSION['swal_icon'] = $result['ok'] ? 'success' : ($result['message'] === 'Bạn đã ứng tuyển công việc này rồi!' ? 'warning' : 'error');
$_SESSION['swal_title'] = $result['message'];

header('Location: job-detail.php?id=' . $jobId);
exit();
