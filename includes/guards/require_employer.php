<?php
/**
 * Guard: chỉ employer đã được admin duyệt mới vào khu employer/*.
 */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'employer') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../services/UserModerationService.php';

$access = UserModerationService::assertEmployerPanelAccess($conn, (int) $_SESSION['user_id']);

if (!$access['ok']) {
    $_SESSION['swal_icon'] = 'warning';
    $_SESSION['swal_title'] = $access['message'];
    header('Location: ../index.php');
    exit;
}
