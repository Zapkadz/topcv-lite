<?php

/**
 * Migration CV-C — từng bước (tránh split SQL + comment gây bỏ qua ALTER đầu).
 *
 * @param PDO $conn
 * @return list<array{sql: string, skip?: callable(PDO): bool}>
 */
function cv_c_migration_steps(PDO $conn): array
{
    $columnExists = static function (string $name) use ($conn): callable {
        return static function () use ($conn, $name): bool {
            $stmt = $conn->prepare('SHOW COLUMNS FROM applications LIKE ?');
            $stmt->execute([$name]);

            return (bool) $stmt->fetch();
        };
    };

    return [
        [
            'sql' => "ALTER TABLE `applications`
                ADD COLUMN `cv_profile_id` int(11) DEFAULT NULL COMMENT 'FK cv_profiles lúc apply'
                AFTER `candidate_id`",
            'skip' => $columnExists('cv_profile_id'),
        ],
        [
            'sql' => "ALTER TABLE `applications`
                ADD COLUMN `cv_snapshot_json` longtext DEFAULT NULL COMMENT 'Bản CV structured bất biến'
                AFTER `cv_snapshot`",
            'skip' => $columnExists('cv_snapshot_json'),
        ],
        [
            'sql' => "ALTER TABLE `applications`
                MODIFY COLUMN `cv_snapshot` varchar(255) DEFAULT NULL
                COMMENT 'File PDF legacy; apply mới dùng JSON'",
        ],
        [
            'sql' => 'ALTER TABLE `applications` ADD KEY `idx_applications_cv_profile` (`cv_profile_id`)',
            'skip' => static function () use ($conn): bool {
                $stmt = $conn->query("SHOW INDEX FROM applications WHERE Key_name = 'idx_applications_cv_profile'");
                return (bool) $stmt->fetch();
            },
        ],
        [
            'sql' => 'ALTER TABLE `applications`
                ADD CONSTRAINT `applications_cv_profile_fk`
                FOREIGN KEY (`cv_profile_id`) REFERENCES `cv_profiles` (`id`) ON DELETE SET NULL',
            'skip' => static function () use ($conn): bool {
                $stmt = $conn->query(
                    "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                     WHERE TABLE_SCHEMA = DATABASE()
                       AND TABLE_NAME = 'applications'
                       AND CONSTRAINT_NAME = 'applications_cv_profile_fk'"
                );
                return (bool) $stmt->fetch();
            },
        ],
    ];
}
