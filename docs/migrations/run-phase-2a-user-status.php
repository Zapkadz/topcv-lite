<?php
/**
 * Chạy một lần: php docs/migrations/run-phase-2a-user-status.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';

$check = $conn->query("SHOW COLUMNS FROM users LIKE 'account_status'");
if ($check->fetch()) {
    echo "SKIP — account_status đã tồn tại.\n";
    exit(0);
}

$sqlFile = __DIR__ . '/phase-2a-user-status.sql';
$sql = file_get_contents($sqlFile);
if ($sql === false) {
    fwrite(STDERR, "FAIL: không đọc được migration SQL.\n");
    exit(1);
}

$statements = array_filter(
    array_map('trim', preg_split('/;\s*\n/', $sql) ?: []),
    fn ($s) => $s !== '' && !str_starts_with($s, '--')
);

try {
    foreach ($statements as $statement) {
        $conn->exec($statement);
    }
    echo "OK — Phase 2A user status migration done.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL: ' . $e->getMessage() . "\n");
    exit(1);
}
