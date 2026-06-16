<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/services/JobRecommendationService.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'candidate') {
    header('Location: ../login.php');
    exit();
}

$redirectUrl = 'job-recommendations.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirectUrl);
    exit();
}

if (!csrf_validate('candidate_job_recommendation_form', $_POST['csrf_token'] ?? '')) {
    $_SESSION['swal_icon'] = 'error';
    $_SESSION['swal_title'] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
    header('Location: ' . $redirectUrl);
    exit();
}

$userId = (int) $_SESSION['user_id'];
$cvProfileId = isset($_POST['cv_profile_id']) ? (int) $_POST['cv_profile_id'] : 0;

$result = JobRecommendationService::runForCandidate($conn, $userId, $cvProfileId);

$_SESSION['swal_icon'] = $result['ok'] ? 'success' : 'error';
$_SESSION['swal_title'] = $result['message'];
if (!empty($result['detail'])) {
    $_SESSION['swal_text'] = (string) $result['detail'];
}
if (!$result['ok']) {
    $_SESSION['swal_persistent'] = true;
}

header('Location: ' . $redirectUrl . ($result['ok'] ? '?ran=1' : ''));
exit();
