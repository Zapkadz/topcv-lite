<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/CvRepository.php';
require_once __DIR__ . '/../cv_rules.php';
require_once __DIR__ . '/../schema_cvs.php';

class CvService
{
    public static function resolveCandidateId(PDO $conn, int $userId): ?int
    {
        $stmt = $conn->prepare('SELECT id FROM candidates WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    public static function ensureCandidateId(PDO $conn, int $userId): int
    {
        $existing = self::resolveCandidateId($conn, $userId);
        if ($existing !== null) {
            return $existing;
        }

        $stmt = $conn->prepare("INSERT INTO candidates (user_id, title, bio) VALUES (?, ?, ?)");
        $stmt->execute([$userId, 'Ứng viên', '']);
        return (int) $conn->lastInsertId();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listForUser(PDO $conn, int $userId): array
    {
        if (!cvs_schema_ready($conn)) {
            return [];
        }
        $candidateId = self::resolveCandidateId($conn, $userId);
        if ($candidateId === null) {
            return [];
        }

        return CvRepository::listByCandidateId($conn, $candidateId);
    }

    /**
     * @return array{ok: bool, message: string, data: array<string, mixed>|null}
     */
    public static function getFullForUser(PDO $conn, int $userId, int $cvId): array
    {
        if (!cvs_schema_ready($conn)) {
            return ['ok' => false, 'message' => 'Schema CV chưa sẵn sàng.', 'data' => null];
        }

        $candidateId = self::resolveCandidateId($conn, $userId);
        if ($candidateId === null) {
            return ['ok' => false, 'message' => 'Không tìm thấy hồ sơ ứng viên.', 'data' => null];
        }

        $profile = CvRepository::findByIdForCandidate($conn, $cvId, $candidateId);
        if (!$profile) {
            return ['ok' => false, 'message' => 'CV không tồn tại hoặc bạn không có quyền.', 'data' => null];
        }

        return [
            'ok' => true,
            'message' => '',
            'data' => self::packFullProfile($conn, $profile),
        ];
    }

    /**
     * @param array<string, mixed> $profile
     * @param array{educations?: list, experiences?: list, skills?: list} $children
     * @return array{ok: bool, message: string, cv_id: int|null}
     */
    public static function createForUser(
        PDO $conn,
        int $userId,
        array $profile,
        array $children = []
    ): array {
        if (!cvs_schema_ready($conn)) {
            return ['ok' => false, 'message' => 'Schema CV chưa sẵn sàng.', 'cv_id' => null];
        }

        $validation = cv_validate_profile($profile);
        if (!$validation['ok']) {
            return ['ok' => false, 'message' => $validation['message'], 'cv_id' => null];
        }

        $candidateId = self::ensureCandidateId($conn, $userId);
        $normalizedChildren = self::normalizeChildren($children);
        $fields = self::normalizeProfileFields($profile, $candidateId, $normalizedChildren);

        if (CvRepository::countByCandidate($conn, $candidateId) === 0) {
            $fields['is_primary'] = 1;
        }

        try {
            $conn->beginTransaction();
            $cvId = CvRepository::insertProfile($conn, $fields);
            CvRepository::insertEducations($conn, $cvId, $normalizedChildren['educations']);
            CvRepository::insertExperiences($conn, $cvId, $normalizedChildren['experiences']);
            CvRepository::insertSkills($conn, $cvId, $normalizedChildren['skills']);
            $conn->commit();

            return ['ok' => true, 'message' => 'Đã tạo CV.', 'cv_id' => $cvId];
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            return ['ok' => false, 'message' => 'Không thể tạo CV: ' . $e->getMessage(), 'cv_id' => null];
        }
    }

    /**
     * @param array<string, mixed> $profile
     * @param array{educations?: list, experiences?: list, skills?: list} $children
     * @return array{ok: bool, message: string}
     */
    public static function saveForUser(
        PDO $conn,
        int $userId,
        int $cvId,
        array $profile,
        array $children = []
    ): array {
        if (!cvs_schema_ready($conn)) {
            return ['ok' => false, 'message' => 'Schema CV chưa sẵn sàng.'];
        }

        $validation = cv_validate_profile($profile);
        if (!$validation['ok']) {
            return ['ok' => false, 'message' => $validation['message']];
        }

        $candidateId = self::resolveCandidateId($conn, $userId);
        if ($candidateId === null) {
            return ['ok' => false, 'message' => 'Không tìm thấy hồ sơ ứng viên.'];
        }

        $existing = CvRepository::findByIdForCandidate($conn, $cvId, $candidateId);
        if (!$existing) {
            return ['ok' => false, 'message' => 'CV không tồn tại hoặc bạn không có quyền.'];
        }

        $normalizedChildren = self::normalizeChildren($children);
        $fields = self::normalizeProfileFields($profile, $candidateId, $normalizedChildren);
        $fields['is_primary'] = (int) ($existing['is_primary'] ?? 0);

        try {
            $conn->beginTransaction();
            CvRepository::updateProfile($conn, $cvId, $fields);
            CvRepository::deleteChildren($conn, $cvId);
            CvRepository::insertEducations($conn, $cvId, $normalizedChildren['educations']);
            CvRepository::insertExperiences($conn, $cvId, $normalizedChildren['experiences']);
            CvRepository::insertSkills($conn, $cvId, $normalizedChildren['skills']);
            $conn->commit();

            return ['ok' => true, 'message' => 'Đã lưu CV.'];
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            return ['ok' => false, 'message' => 'Không thể lưu CV: ' . $e->getMessage()];
        }
    }

    public static function deleteForUser(PDO $conn, int $userId, int $cvId): array
    {
        if (!cvs_schema_ready($conn)) {
            return ['ok' => false, 'message' => 'Schema CV chưa sẵn sàng.'];
        }

        $candidateId = self::resolveCandidateId($conn, $userId);
        if ($candidateId === null) {
            return ['ok' => false, 'message' => 'Không tìm thấy hồ sơ ứng viên.'];
        }

        $existing = CvRepository::findByIdForCandidate($conn, $cvId, $candidateId);
        if (!$existing) {
            return ['ok' => false, 'message' => 'CV không tồn tại hoặc bạn không có quyền.'];
        }

        $wasPrimary = (int) ($existing['is_primary'] ?? 0) === 1;

        if (!CvRepository::deleteProfile($conn, $cvId, $candidateId)) {
            return ['ok' => false, 'message' => 'Không thể xóa CV.'];
        }

        if ($wasPrimary) {
            $remaining = CvRepository::listByCandidateId($conn, $candidateId);
            if (count($remaining) > 0) {
                CvRepository::setPrimary($conn, (int) $remaining[0]['id'], $candidateId);
            }
        }

        return ['ok' => true, 'message' => 'Đã xóa CV.'];
    }

    public static function setPrimaryForUser(PDO $conn, int $userId, int $cvId): array
    {
        if (!cvs_schema_ready($conn)) {
            return ['ok' => false, 'message' => 'Schema CV chưa sẵn sàng.'];
        }

        $candidateId = self::resolveCandidateId($conn, $userId);
        if ($candidateId === null) {
            return ['ok' => false, 'message' => 'Không tìm thấy hồ sơ ứng viên.'];
        }

        if (!CvRepository::setPrimary($conn, $cvId, $candidateId)) {
            return ['ok' => false, 'message' => 'Không thể đặt CV mặc định.'];
        }

        return ['ok' => true, 'message' => 'Đã đặt làm CV mặc định.'];
    }

    public static function buildSnapshotJson(PDO $conn, int $cvId): ?string
    {
        $profile = CvRepository::findById($conn, $cvId);
        if (!$profile) {
            return null;
        }

        $payload = self::packFullProfile($conn, $profile);
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        return $json !== false ? $json : null;
    }

    /**
     * @param array<string, mixed> $profile
     * @return array<string, mixed>
     */
    private static function packFullProfile(PDO $conn, array $profile): array
    {
        $cvId = (int) $profile['id'];

        return [
            'profile' => $profile,
            'educations' => CvRepository::listEducations($conn, $cvId),
            'experiences' => CvRepository::listExperiences($conn, $cvId),
            'skills' => CvRepository::listSkills($conn, $cvId),
            'snapshot_at' => date('c'),
        ];
    }

    /**
     * @param array{educations?: list, experiences?: list, skills?: list} $children
     * @return array{educations: list, experiences: list, skills: list}
     */
    private static function normalizeChildren(array $children): array
    {
        return [
            'educations' => array_values($children['educations'] ?? []),
            'experiences' => array_values($children['experiences'] ?? []),
            'skills' => array_values($children['skills'] ?? []),
        ];
    }

    /**
     * @param array<string, mixed> $profile
     * @param array{educations: list, experiences: list, skills: list} $children
     * @return array<string, mixed>
     */
    private static function normalizeProfileFields(
        array $profile,
        int $candidateId,
        array $children
    ): array {
        $dob = trim((string) ($profile['date_of_birth'] ?? ''));

        $normalized = [
            'candidate_id' => $candidateId,
            'title' => trim((string) ($profile['title'] ?? '')),
            'full_name' => trim((string) ($profile['full_name'] ?? '')),
            'target_position' => trim((string) ($profile['target_position'] ?? '')),
            'date_of_birth' => $dob !== '' ? $dob : null,
            'gender' => self::nullIfEmpty(trim((string) ($profile['gender'] ?? ''))),
            'phone' => self::nullIfEmpty(trim((string) ($profile['phone'] ?? ''))),
            'email' => self::nullIfEmpty(trim((string) ($profile['email'] ?? ''))),
            'website' => self::nullIfEmpty(trim((string) ($profile['website'] ?? ''))),
            'address' => self::nullIfEmpty(trim((string) ($profile['address'] ?? ''))),
            'avatar_path' => self::nullIfEmpty(trim((string) ($profile['avatar_path'] ?? ''))),
            'career_objective' => self::nullIfEmpty(trim((string) ($profile['career_objective'] ?? ''))),
            'interests' => self::nullIfEmpty(trim((string) ($profile['interests'] ?? ''))),
            'attachment_path' => self::nullIfEmpty(trim((string) ($profile['attachment_path'] ?? ''))),
            'template_key' => trim((string) ($profile['template_key'] ?? 'classic')) ?: 'classic',
            'is_primary' => (int) ($profile['is_primary'] ?? 0),
            'completion_percent' => cv_estimate_completion_percent($profile, $children),
        ];

        return $normalized;
    }

    private static function nullIfEmpty(string $value): ?string
    {
        return $value === '' ? null : $value;
    }
}
