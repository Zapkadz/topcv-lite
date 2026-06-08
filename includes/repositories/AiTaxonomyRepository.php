<?php
declare(strict_types=1);

class AiTaxonomyRepository
{
    public static function findSuggestionById(PDO $conn, int $id): ?array
    {
        $stmt = $conn->prepare('SELECT * FROM ai_taxonomy_suggestions WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function findSuggestionBySuggestionId(PDO $conn, string $suggestionId): ?array
    {
        $stmt = $conn->prepare('SELECT * FROM ai_taxonomy_suggestions WHERE suggestion_id = ? LIMIT 1');
        $stmt->execute([$suggestionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @param array{status?: string, search?: string, sort?: string, dir?: string} $filters
     * @return list<array<string, mixed>>
     */
    public static function listSuggestions(PDO $conn, array $filters = []): array
    {
        $where = ['1=1'];
        $params = [];

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '') {
            $where[] = 'status = ?';
            $params[] = $status;
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $where[] = '(suggested_canonical_name LIKE ? OR suggested_aliases_json LIKE ? OR suggestion_id LIKE ?)';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sort = (string) ($filters['sort'] ?? 'created_at');
        $allowedSort = ['frequency', 'confidence', 'created_at', 'suggested_canonical_name'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'created_at';
        }

        $dir = strtoupper((string) ($filters['dir'] ?? 'DESC'));
        $dir = $dir === 'ASC' ? 'ASC' : 'DESC';

        $sql = 'SELECT * FROM ai_taxonomy_suggestions WHERE ' . implode(' AND ', $where)
            . " ORDER BY {$sort} {$dir}, id DESC";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function countByStatus(PDO $conn, string $status): int
    {
        $stmt = $conn->prepare('SELECT COUNT(*) FROM ai_taxonomy_suggestions WHERE status = ?');
        $stmt->execute([$status]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @param array<string, mixed> $fields
     */
    public static function insertSuggestion(PDO $conn, array $fields): int
    {
        $stmt = $conn->prepare(
            'INSERT INTO ai_taxonomy_suggestions (
                suggestion_id, suggested_canonical_name, suggested_category,
                suggested_aliases_json, frequency, confidence,
                nearest_existing_skills_json, example_contexts_json,
                example_evidence_json, raw_json, status
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $fields['suggestion_id'],
            $fields['suggested_canonical_name'],
            $fields['suggested_category'],
            $fields['suggested_aliases_json'],
            $fields['frequency'],
            $fields['confidence'],
            $fields['nearest_existing_skills_json'],
            $fields['example_contexts_json'],
            $fields['example_evidence_json'],
            $fields['raw_json'],
            $fields['status'] ?? 'pending_review',
        ]);

        return (int) $conn->lastInsertId();
    }

    /**
     * @param array<string, mixed> $fields
     */
    public static function updatePendingSuggestion(PDO $conn, int $id, array $fields): bool
    {
        $stmt = $conn->prepare(
            'UPDATE ai_taxonomy_suggestions SET
                suggested_canonical_name = ?, suggested_category = ?,
                suggested_aliases_json = ?, frequency = ?, confidence = ?,
                nearest_existing_skills_json = ?, example_contexts_json = ?,
                example_evidence_json = ?, raw_json = ?, updated_at = NOW()
             WHERE id = ? AND status = \'pending_review\''
        );

        return $stmt->execute([
            $fields['suggested_canonical_name'],
            $fields['suggested_category'],
            $fields['suggested_aliases_json'],
            $fields['frequency'],
            $fields['confidence'],
            $fields['nearest_existing_skills_json'],
            $fields['example_contexts_json'],
            $fields['example_evidence_json'],
            $fields['raw_json'],
            $id,
        ]);
    }

    /**
     * @param array<string, mixed> $fields
     */
    public static function updateSuggestionDecision(PDO $conn, int $id, array $fields): bool
    {
        $stmt = $conn->prepare(
            'UPDATE ai_taxonomy_suggestions SET
                status = ?, decision_type = ?, decision_note = ?,
                target_skill_name = ?, reviewed_by = ?, reviewed_at = NOW(), updated_at = NOW()
             WHERE id = ?'
        );

        return $stmt->execute([
            $fields['status'],
            $fields['decision_type'],
            $fields['decision_note'],
            $fields['target_skill_name'],
            $fields['reviewed_by'],
            $id,
        ]);
    }

    public static function findCustomSkillByName(PDO $conn, string $skillName): ?array
    {
        $stmt = $conn->prepare(
            'SELECT * FROM ai_custom_taxonomy_skills WHERE skill_name = ? AND status = \'active\' LIMIT 1'
        );
        $stmt->execute([$skillName]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listActiveCustomSkills(PDO $conn): array
    {
        $stmt = $conn->query(
            'SELECT * FROM ai_custom_taxonomy_skills WHERE status = \'active\' ORDER BY skill_name ASC'
        );

        return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    }

    /**
     * @param array<string, mixed> $fields
     */
    public static function insertCustomSkill(PDO $conn, array $fields): int
    {
        $stmt = $conn->prepare(
            'INSERT INTO ai_custom_taxonomy_skills (
                skill_name, category, aliases_json, related_json, transferable_json,
                source_suggestion_id, status, created_by
             ) VALUES (?, ?, ?, ?, ?, ?, \'active\', ?)'
        );
        $stmt->execute([
            $fields['skill_name'],
            $fields['category'],
            $fields['aliases_json'],
            $fields['related_json'],
            $fields['transferable_json'],
            $fields['source_suggestion_id'],
            $fields['created_by'],
        ]);

        return (int) $conn->lastInsertId();
    }

    public static function updateCustomSkillAliases(
        PDO $conn,
        int $id,
        string $aliasesJson,
        ?string $category = null
    ): bool {
        if ($category !== null) {
            $stmt = $conn->prepare(
                'UPDATE ai_custom_taxonomy_skills SET aliases_json = ?, category = ?, updated_at = NOW() WHERE id = ?'
            );

            return $stmt->execute([$aliasesJson, $category, $id]);
        }

        $stmt = $conn->prepare(
            'UPDATE ai_custom_taxonomy_skills SET aliases_json = ?, updated_at = NOW() WHERE id = ?'
        );

        return $stmt->execute([$aliasesJson, $id]);
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    public static function insertAuditLog(
        PDO $conn,
        string $action,
        ?string $suggestionId,
        ?string $oldStatus,
        ?string $newStatus,
        ?array $payload,
        ?int $adminId
    ): void {
        $stmt = $conn->prepare(
            'INSERT INTO ai_taxonomy_audit_logs (
                suggestion_id, action, old_status, new_status, payload_json, admin_id
             ) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $suggestionId,
            $action,
            $oldStatus,
            $newStatus,
            $payload !== null ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
            $adminId,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listRecentAuditLogs(PDO $conn, int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        $stmt = $conn->prepare(
            'SELECT l.*, u.fullname AS admin_name
             FROM ai_taxonomy_audit_logs l
             LEFT JOIN users u ON u.id = l.admin_id
             ORDER BY l.created_at DESC
             LIMIT ' . $limit
        );
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
