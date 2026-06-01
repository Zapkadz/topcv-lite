<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/schema_moderation.php';

if (moderation_schema_ready($conn)) {
    echo "SKIP — moderation_logs đã tồn tại.\n";
    exit(0);
}

$sql = file_get_contents(__DIR__ . '/phase-2c-moderation-logs.sql');
if ($sql === false) {
    fwrite(STDERR, "FAIL: không đọc được SQL.\n");
    exit(1);
}

try {
    $conn->exec($sql);
    echo "OK — Phase 2C moderation_logs migration done.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL: ' . $e->getMessage() . "\n");
    exit(1);
}
