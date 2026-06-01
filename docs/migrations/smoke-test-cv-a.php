<?php
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($remote, ['127.0.0.1', '::1'], true)) {
        http_response_code(403);
        exit('Chỉ chạy trên localhost.');
    }
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/schema_cvs.php';
require_once __DIR__ . '/../../includes/services/CvService.php';

header('Content-Type: text/plain; charset=utf-8');

if (!cvs_schema_ready($conn)) {
    echo "FAIL — Chạy migrate-phase-cv-a.php trước.\n";
    exit(1);
}

$stmt = $conn->query("SELECT u.id FROM users u WHERE u.role = 'candidate' LIMIT 1");
$userId = (int) ($stmt->fetchColumn() ?: 0);
if ($userId <= 0) {
    echo "FAIL — Không có user candidate trong DB.\n";
    exit(1);
}

$profileBase = [
    'title' => 'CV Smoke Test 1',
    'full_name' => 'Test Candidate',
    'target_position' => 'Developer',
    'phone' => '0901234567',
    'email' => 'smoke@test.local',
    'career_objective' => 'Mục tiêu test',
];

$children1 = [
    'educations' => [
        ['school_name' => 'ĐH Test', 'major' => 'IT', 'start_date' => '2020', 'end_date' => '2024'],
    ],
    'experiences' => [
        ['company_name' => 'Cty A', 'position' => 'Intern', 'start_date' => '2024', 'end_date' => '2025'],
    ],
    'skills' => [
        ['skill_name' => 'PHP', 'description' => 'Cơ bản'],
    ],
];

$r1 = CvService::createForUser($conn, $userId, $profileBase, $children1);
if (!$r1['ok'] || !$r1['cv_id']) {
    echo 'FAIL create cv1: ' . $r1['message'] . "\n";
    exit(1);
}
$cv1 = (int) $r1['cv_id'];

$profile2 = $profileBase;
$profile2['title'] = 'CV Smoke Test 2';
$profile2['full_name'] = 'Test Candidate Two';
$r2 = CvService::createForUser($conn, $userId, $profile2, ['educations' => [], 'experiences' => [], 'skills' => []]);
if (!$r2['ok'] || !$r2['cv_id']) {
    echo 'FAIL create cv2: ' . $r2['message'] . "\n";
    exit(1);
}
$cv2 = (int) $r2['cv_id'];

$g1 = CvService::getFullForUser($conn, $userId, $cv1);
$g2 = CvService::getFullForUser($conn, $userId, $cv2);
if (!$g1['ok'] || !$g2['ok']) {
    echo "FAIL get full\n";
    exit(1);
}

if (($g1['data']['profile']['full_name'] ?? '') === ($g2['data']['profile']['full_name'] ?? '')) {
    echo "FAIL — cv1 và cv2 trùng data\n";
    exit(1);
}

$list = CvService::listForUser($conn, $userId);
if (count($list) < 2) {
    echo "FAIL — list < 2 CV\n";
    exit(1);
}

$primaryCount = 0;
foreach ($list as $row) {
    if ((int) ($row['is_primary'] ?? 0) === 1) {
        $primaryCount++;
    }
}
if ($primaryCount !== 1) {
    echo "FAIL — is_primary count = {$primaryCount}, expected 1\n";
    exit(1);
}

$snap = CvService::buildSnapshotJson($conn, $cv1);
if ($snap === null || $snap === '') {
    echo "FAIL — snapshot json empty\n";
    exit(1);
}

echo "OK — CV-A smoke test passed.\n";
echo "  user_id={$userId} cv1={$cv1} cv2={$cv2}\n";
echo "  primary OK, snapshot length=" . strlen($snap) . "\n";
