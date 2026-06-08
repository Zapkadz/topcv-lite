<?php
declare(strict_types=1);

require_once __DIR__ . '/schema_cvs.php';
require_once __DIR__ . '/repositories/CvRepository.php';

if (!function_exists('profile_validate_legacy_cv_path')) {
    /**
     * Chuẩn hoá path file CV cũ trên candidates.cv_path (chống path traversal).
     */
    function profile_validate_legacy_cv_path(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));
        if ($path === '') {
            return '';
        }

        if (str_contains($path, '..') || str_starts_with($path, '/') || str_contains($path, '://')) {
            return '';
        }

        if (!str_starts_with($path, 'uploads/')) {
            return '';
        }

        return $path;
    }
}

if (!function_exists('profile_count_pending_cv_path')) {
    function profile_count_pending_cv_path(PDO $conn): int
    {
        $stmt = $conn->query(
            "SELECT COUNT(*) FROM candidates
             WHERE cv_path IS NOT NULL AND TRIM(cv_path) <> ''"
        );
        $count = $stmt ? $stmt->fetchColumn() : 0;

        return (int) $count;
    }
}

if (!function_exists('profile_migrate_cv_path_batch')) {
    /**
     * Chuyển candidates.cv_path sang cv_profiles.attachment_path rồi xoá cv_path.
     *
     * @return array{
     *   ok: bool,
     *   message: string,
     *   dry_run: bool,
     *   stats: array<string, int>,
     *   details: list<array<string, mixed>>
     * }
     */
    function profile_migrate_cv_path_batch(PDO $conn, bool $dryRun = false): array
    {
        $stats = [
            'pending' => 0,
            'migrated' => 0,
            'updated_primary' => 0,
            'updated_existing' => 0,
            'inserted_new' => 0,
            'inserted_legacy' => 0,
            'skipped_invalid' => 0,
            'file_missing' => 0,
        ];
        $details = [];

        if (!cvs_schema_ready($conn)) {
            return [
                'ok' => false,
                'message' => 'Chưa có schema CV structured. Chạy migrate-phase-cv-a.php trước.',
                'dry_run' => $dryRun,
                'stats' => $stats,
                'details' => $details,
            ];
        }

        $stmt = $conn->query(
            'SELECT c.id AS candidate_id, c.user_id, c.title AS candidate_title, c.cv_path,
                    u.fullname, u.email, u.phone
             FROM candidates c
             INNER JOIN users u ON u.id = c.user_id
             WHERE c.cv_path IS NOT NULL AND TRIM(c.cv_path) <> \'\''
        );
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        if (!is_array($rows)) {
            $rows = [];
        }

        $stats['pending'] = count($rows);
        if ($rows === []) {
            return [
                'ok' => true,
                'message' => 'Không còn candidates.cv_path cần migrate.',
                'dry_run' => $dryRun,
                'stats' => $stats,
                'details' => $details,
            ];
        }

        $projectRoot = dirname(__DIR__);
        $clearCvPath = $conn->prepare('UPDATE candidates SET cv_path = NULL WHERE id = ?');
        $setAttachment = $conn->prepare(
            'UPDATE cv_profiles SET attachment_path = ?
             WHERE id = ? AND candidate_id = ?
               AND (attachment_path IS NULL OR TRIM(attachment_path) = \'\')'
        );

        foreach ($rows as $row) {
            $candidateId = (int) ($row['candidate_id'] ?? 0);
            $userId = (int) ($row['user_id'] ?? 0);
            $rawPath = trim((string) ($row['cv_path'] ?? ''));
            $cvPath = profile_validate_legacy_cv_path($rawPath);

            if ($cvPath === '') {
                $stats['skipped_invalid']++;
                $details[] = [
                    'candidate_id' => $candidateId,
                    'user_id' => $userId,
                    'cv_path' => $rawPath,
                    'action' => 'skipped_invalid',
                    'note' => 'Path không hợp lệ — cv_path giữ nguyên.',
                ];
                continue;
            }

            $absolute = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cvPath);
            $fileMissing = !is_file($absolute);
            if ($fileMissing) {
                $stats['file_missing']++;
            }

            $profiles = CvRepository::listByCandidateId($conn, $candidateId);
            $targetId = null;
            $action = '';

            foreach ($profiles as $profile) {
                if ((int) ($profile['is_primary'] ?? 0) !== 1) {
                    continue;
                }
                if (trim((string) ($profile['attachment_path'] ?? '')) === '') {
                    $targetId = (int) ($profile['id'] ?? 0);
                    $action = 'updated_primary';
                    break;
                }
            }

            if ($targetId === null) {
                foreach ($profiles as $profile) {
                    if (trim((string) ($profile['attachment_path'] ?? '')) === '') {
                        $targetId = (int) ($profile['id'] ?? 0);
                        $action = 'updated_existing';
                        break;
                    }
                }
            }

            if ($targetId === null && $profiles === []) {
                $action = 'inserted_new';
            } elseif ($targetId === null) {
                $action = 'inserted_legacy';
            }

            if (!$dryRun) {
                try {
                    $conn->beginTransaction();

                    if ($action === 'updated_primary' || $action === 'updated_existing') {
                        $setAttachment->execute([$cvPath, $targetId, $candidateId]);
                    } elseif ($action === 'inserted_new') {
                        $title = trim((string) ($row['candidate_title'] ?? ''));
                        if ($title === '') {
                            $title = 'CV từ file hồ sơ cũ';
                        }
                        $targetId = CvRepository::insertProfile($conn, [
                            'candidate_id' => $candidateId,
                            'title' => $title,
                            'full_name' => trim((string) ($row['fullname'] ?? '')),
                            'target_position' => trim((string) ($row['candidate_title'] ?? '')) ?: null,
                            'date_of_birth' => null,
                            'gender' => null,
                            'phone' => trim((string) ($row['phone'] ?? '')) ?: null,
                            'email' => trim((string) ($row['email'] ?? '')) ?: null,
                            'website' => null,
                            'address' => null,
                            'avatar_path' => null,
                            'career_objective' => null,
                            'interests' => null,
                            'attachment_path' => $cvPath,
                            'template_key' => 'classic',
                            'is_primary' => 1,
                            'completion_percent' => 10,
                        ]);
                    } elseif ($action === 'inserted_legacy') {
                        $targetId = CvRepository::insertProfile($conn, [
                            'candidate_id' => $candidateId,
                            'title' => 'CV file cũ (hồ sơ)',
                            'full_name' => trim((string) ($row['fullname'] ?? '')),
                            'target_position' => null,
                            'date_of_birth' => null,
                            'gender' => null,
                            'phone' => trim((string) ($row['phone'] ?? '')) ?: null,
                            'email' => trim((string) ($row['email'] ?? '')) ?: null,
                            'website' => null,
                            'address' => null,
                            'avatar_path' => null,
                            'career_objective' => null,
                            'interests' => null,
                            'attachment_path' => $cvPath,
                            'template_key' => 'classic',
                            'is_primary' => 0,
                            'completion_percent' => 5,
                        ]);
                    }

                    $clearCvPath->execute([$candidateId]);
                    $conn->commit();
                } catch (Throwable $e) {
                    if ($conn->inTransaction()) {
                        $conn->rollBack();
                    }

                    return [
                        'ok' => false,
                        'message' => 'Lỗi candidate #' . $candidateId . ': ' . $e->getMessage(),
                        'dry_run' => $dryRun,
                        'stats' => $stats,
                        'details' => $details,
                    ];
                }
            }

            $stats['migrated']++;
            $stats[$action]++;
            $details[] = [
                'candidate_id' => $candidateId,
                'user_id' => $userId,
                'cv_path' => $cvPath,
                'action' => $action,
                'cv_profile_id' => $targetId,
                'file_missing' => $fileMissing,
            ];
        }

        $verb = $dryRun ? 'Dry-run' : 'Migration';

        return [
            'ok' => true,
            'message' => $verb . ' cv_path hoàn tất: ' . $stats['migrated'] . '/' . $stats['pending'] . ' bản ghi.',
            'dry_run' => $dryRun,
            'stats' => $stats,
            'details' => $details,
        ];
    }
}
