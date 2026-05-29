<?php
/**
 * Chạy một lần: php docs/migrations/run-phase-1-1-locations.php
 * Seed 36 địa điểm (UTF-8) — an toàn hơn pipe SQL trên Windows.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';

$renames = [
    'Hà Nội' => 'TP. Hà Nội',
    'Hồ Chí Minh' => 'TP. Hồ Chí Minh',
    'Đà Nẵng' => 'TP. Đà Nẵng',
    'Cần Thơ' => 'TP. Cần Thơ',
    'Toàn Quốc' => 'Remote',
];

$insertNames = [
    'Tuyên Quang', 'Cao Bằng', 'Lào Cai', 'Lai Châu', 'Điện Biên', 'Sơn La', 'Lạng Sơn',
    'Thái Nguyên', 'Phú Thọ', 'Bắc Ninh', 'Quảng Ninh', 'Hưng Yên', 'Ninh Bình', 'Thanh Hóa',
    'Nghệ An', 'Hà Tĩnh', 'Quảng Trị', 'Quảng Ngãi', 'Gia Lai', 'Đắk Lắk', 'Khánh Hòa',
    'Lâm Đồng', 'Đồng Nai', 'Tây Ninh', 'Đồng Tháp', 'Vĩnh Long', 'Cà Mau', 'An Giang',
    'TP. Huế', 'TP. Hải Phòng', 'Khác',
];

$conn->beginTransaction();
try {
    $upd = $conn->prepare('UPDATE locations SET name = ? WHERE name = ?');
    foreach ($renames as $old => $new) {
        $upd->execute([$new, $old]);
    }

    $check = $conn->prepare('SELECT id FROM locations WHERE name = ? LIMIT 1');
    $ins = $conn->prepare('INSERT INTO locations (name) VALUES (?)');
    foreach ($insertNames as $name) {
        $check->execute([$name]);
        if (!$check->fetch()) {
            $ins->execute([$name]);
        }
    }

    $conn->commit();
    $count = (int) $conn->query('SELECT COUNT(*) FROM locations')->fetchColumn();
    echo "OK — locations count: $count\n";
} catch (Throwable $e) {
    $conn->rollBack();
    fwrite(STDERR, 'FAIL: ' . $e->getMessage() . "\n");
    exit(1);
}
