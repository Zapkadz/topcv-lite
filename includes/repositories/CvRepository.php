<?php
declare(strict_types=1);

class CvRepository
{
    public static function findById(PDO $conn, int $cvId): ?array
    {
        $stmt = $conn->prepare('SELECT * FROM cv_profiles WHERE id = ? LIMIT 1');
        $stmt->execute([$cvId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function findByIdForCandidate(PDO $conn, int $cvId, int $candidateId): ?array
    {
        $stmt = $conn->prepare(
            'SELECT * FROM cv_profiles WHERE id = ? AND candidate_id = ? LIMIT 1'
        );
        $stmt->execute([$cvId, $candidateId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listByCandidateId(PDO $conn, int $candidateId): array
    {
        $stmt = $conn->prepare(
            'SELECT * FROM cv_profiles WHERE candidate_id = ? ORDER BY is_primary DESC, updated_at DESC'
        );
        $stmt->execute([$candidateId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function insertProfile(PDO $conn, array $fields): int
    {
        $stmt = $conn->prepare(
            'INSERT INTO cv_profiles (
                candidate_id, title, full_name, target_position, date_of_birth, gender,
                phone, email, website, address, avatar_path, career_objective, interests,
                attachment_path, template_key, is_primary, completion_percent
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $fields['candidate_id'],
            $fields['title'],
            $fields['full_name'],
            $fields['target_position'],
            $fields['date_of_birth'],
            $fields['gender'],
            $fields['phone'],
            $fields['email'],
            $fields['website'],
            $fields['address'],
            $fields['avatar_path'],
            $fields['career_objective'],
            $fields['interests'],
            $fields['attachment_path'],
            $fields['template_key'],
            $fields['is_primary'],
            $fields['completion_percent'],
        ]);

        return (int) $conn->lastInsertId();
    }

    public static function updateProfile(PDO $conn, int $cvId, array $fields): bool
    {
        $stmt = $conn->prepare(
            'UPDATE cv_profiles SET
                title = ?, full_name = ?, target_position = ?, date_of_birth = ?, gender = ?,
                phone = ?, email = ?, website = ?, address = ?, avatar_path = ?,
                career_objective = ?, interests = ?, attachment_path = ?, template_key = ?,
                completion_percent = ?
             WHERE id = ?'
        );

        return $stmt->execute([
            $fields['title'],
            $fields['full_name'],
            $fields['target_position'],
            $fields['date_of_birth'],
            $fields['gender'],
            $fields['phone'],
            $fields['email'],
            $fields['website'],
            $fields['address'],
            $fields['avatar_path'],
            $fields['career_objective'],
            $fields['interests'],
            $fields['attachment_path'],
            $fields['template_key'],
            $fields['completion_percent'],
            $cvId,
        ]);
    }

    public static function deleteProfile(PDO $conn, int $cvId, int $candidateId): bool
    {
        $stmt = $conn->prepare('DELETE FROM cv_profiles WHERE id = ? AND candidate_id = ?');
        $stmt->execute([$cvId, $candidateId]);

        return $stmt->rowCount() > 0;
    }

    public static function clearPrimaryForCandidate(PDO $conn, int $candidateId): void
    {
        $stmt = $conn->prepare('UPDATE cv_profiles SET is_primary = 0 WHERE candidate_id = ?');
        $stmt->execute([$candidateId]);
    }

    public static function setPrimary(PDO $conn, int $cvId, int $candidateId): bool
    {
        self::clearPrimaryForCandidate($conn, $candidateId);
        $stmt = $conn->prepare(
            'UPDATE cv_profiles SET is_primary = 1 WHERE id = ? AND candidate_id = ?'
        );
        $stmt->execute([$cvId, $candidateId]);

        return $stmt->rowCount() > 0;
    }

    public static function countByCandidate(PDO $conn, int $candidateId): int
    {
        $stmt = $conn->prepare('SELECT COUNT(*) FROM cv_profiles WHERE candidate_id = ?');
        $stmt->execute([$candidateId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listEducations(PDO $conn, int $cvId): array
    {
        $stmt = $conn->prepare(
            'SELECT * FROM cv_educations WHERE cv_id = ? ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([$cvId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listExperiences(PDO $conn, int $cvId): array
    {
        $stmt = $conn->prepare(
            'SELECT * FROM cv_experiences WHERE cv_id = ? ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([$cvId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listSkills(PDO $conn, int $cvId): array
    {
        $stmt = $conn->prepare(
            'SELECT * FROM cv_skills WHERE cv_id = ? ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([$cvId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listActivities(PDO $conn, int $cvId): array
    {
        $stmt = $conn->prepare(
            'SELECT * FROM cv_activities WHERE cv_id = ? ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([$cvId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listCertificates(PDO $conn, int $cvId): array
    {
        $stmt = $conn->prepare(
            'SELECT * FROM cv_certificates WHERE cv_id = ? ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([$cvId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listAwards(PDO $conn, int $cvId): array
    {
        $stmt = $conn->prepare(
            'SELECT * FROM cv_awards WHERE cv_id = ? ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([$cvId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listReferences(PDO $conn, int $cvId): array
    {
        $stmt = $conn->prepare(
            'SELECT * FROM cv_references WHERE cv_id = ? ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([$cvId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function deleteChildren(PDO $conn, int $cvId, bool $includeExtended = true): void
    {
        $conn->prepare('DELETE FROM cv_educations WHERE cv_id = ?')->execute([$cvId]);
        $conn->prepare('DELETE FROM cv_experiences WHERE cv_id = ?')->execute([$cvId]);
        $conn->prepare('DELETE FROM cv_skills WHERE cv_id = ?')->execute([$cvId]);
        if (!$includeExtended) {
            return;
        }
        try {
            $conn->prepare('DELETE FROM cv_activities WHERE cv_id = ?')->execute([$cvId]);
            $conn->prepare('DELETE FROM cv_certificates WHERE cv_id = ?')->execute([$cvId]);
            $conn->prepare('DELETE FROM cv_awards WHERE cv_id = ?')->execute([$cvId]);
            $conn->prepare('DELETE FROM cv_references WHERE cv_id = ?')->execute([$cvId]);
        } catch (Throwable $e) {
            // Bảng CV-D chưa có — bỏ qua
        }
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    public static function insertEducations(PDO $conn, int $cvId, array $rows): void
    {
        $stmt = $conn->prepare(
            'INSERT INTO cv_educations (cv_id, start_date, end_date, school_name, major, description, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($rows as $i => $row) {
            $stmt->execute([
                $cvId,
                $row['start_date'] ?? null,
                $row['end_date'] ?? null,
                $row['school_name'] ?? '',
                $row['major'] ?? null,
                $row['description'] ?? null,
                $row['sort_order'] ?? $i,
            ]);
        }
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    public static function insertExperiences(PDO $conn, int $cvId, array $rows): void
    {
        $stmt = $conn->prepare(
            'INSERT INTO cv_experiences (cv_id, start_date, end_date, company_name, position, description, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($rows as $i => $row) {
            $stmt->execute([
                $cvId,
                $row['start_date'] ?? null,
                $row['end_date'] ?? null,
                $row['company_name'] ?? '',
                $row['position'] ?? null,
                $row['description'] ?? null,
                $row['sort_order'] ?? $i,
            ]);
        }
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    public static function insertSkills(PDO $conn, int $cvId, array $rows): void
    {
        $stmt = $conn->prepare(
            'INSERT INTO cv_skills (cv_id, skill_name, description, sort_order)
             VALUES (?, ?, ?, ?)'
        );
        foreach ($rows as $i => $row) {
            $stmt->execute([
                $cvId,
                $row['skill_name'] ?? '',
                $row['description'] ?? null,
                $row['sort_order'] ?? $i,
            ]);
        }
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    public static function insertActivities(PDO $conn, int $cvId, array $rows): void
    {
        $stmt = $conn->prepare(
            'INSERT INTO cv_activities (cv_id, start_date, end_date, organization, role, description, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($rows as $i => $row) {
            $stmt->execute([
                $cvId,
                $row['start_date'] ?? null,
                $row['end_date'] ?? null,
                $row['organization'] ?? '',
                $row['role'] ?? null,
                $row['description'] ?? null,
                $row['sort_order'] ?? $i,
            ]);
        }
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    public static function insertCertificates(PDO $conn, int $cvId, array $rows): void
    {
        $stmt = $conn->prepare(
            'INSERT INTO cv_certificates (cv_id, issued_at, certificate_name, sort_order)
             VALUES (?, ?, ?, ?)'
        );
        foreach ($rows as $i => $row) {
            $stmt->execute([
                $cvId,
                $row['issued_at'] ?? null,
                $row['certificate_name'] ?? '',
                $row['sort_order'] ?? $i,
            ]);
        }
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    public static function insertAwards(PDO $conn, int $cvId, array $rows): void
    {
        $stmt = $conn->prepare(
            'INSERT INTO cv_awards (cv_id, awarded_at, title, description, sort_order)
             VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($rows as $i => $row) {
            $stmt->execute([
                $cvId,
                $row['awarded_at'] ?? null,
                $row['title'] ?? '',
                $row['description'] ?? null,
                $row['sort_order'] ?? $i,
            ]);
        }
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    public static function insertReferences(PDO $conn, int $cvId, array $rows): void
    {
        $stmt = $conn->prepare(
            'INSERT INTO cv_references (cv_id, full_name, position, contact_info, sort_order)
             VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($rows as $i => $row) {
            $stmt->execute([
                $cvId,
                $row['full_name'] ?? '',
                $row['position'] ?? null,
                $row['contact_info'] ?? null,
                $row['sort_order'] ?? $i,
            ]);
        }
    }
}
