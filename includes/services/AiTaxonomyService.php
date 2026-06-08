<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/AiTaxonomyRepository.php';
require_once __DIR__ . '/../ai_taxonomy_config.php';
require_once __DIR__ . '/../schema_ai_taxonomy.php';

class AiTaxonomyService
{
    private const REVIEWED_STATUSES = ['approved_new_skill', 'approved_alias', 'merged', 'rejected'];

    /**
     * @return array{ok: bool, message: string, imported: int, skipped: int}
     */
    public static function importFromPath(PDO $conn, string $path, int $adminId): array
    {
        $path = trim($path);
        if ($path === '' || !is_file($path)) {
            return ['ok' => false, 'message' => 'Không tìm thấy file taxonomy suggestions.', 'imported' => 0, 'skipped' => 0];
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            return ['ok' => false, 'message' => 'Không đọc được file JSON.', 'imported' => 0, 'skipped' => 0];
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return ['ok' => false, 'message' => 'JSON không hợp lệ.', 'imported' => 0, 'skipped' => 0];
        }

        return self::importFromData($conn, $data, $adminId, $path);
    }

    /**
     * @param array<string, mixed> $uploaded $_FILES entry
     * @return array{ok: bool, message: string, imported: int, skipped: int}
     */
    public static function importFromUpload(PDO $conn, array $uploaded, int $adminId): array
    {
        $error = (int) ($uploaded['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'message' => 'Upload thất bại hoặc chưa chọn file.', 'imported' => 0, 'skipped' => 0];
        }

        $tmp = (string) ($uploaded['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['ok' => false, 'message' => 'File upload không hợp lệ.', 'imported' => 0, 'skipped' => 0];
        }

        $size = (int) ($uploaded['size'] ?? 0);
        if ($size <= 0 || $size > 5 * 1024 * 1024) {
            return ['ok' => false, 'message' => 'File quá lớn (tối đa 5MB).', 'imported' => 0, 'skipped' => 0];
        }

        $raw = file_get_contents($tmp);
        if ($raw === false) {
            return ['ok' => false, 'message' => 'Không đọc được file upload.', 'imported' => 0, 'skipped' => 0];
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return ['ok' => false, 'message' => 'JSON không hợp lệ.', 'imported' => 0, 'skipped' => 0];
        }

        return self::importFromData($conn, $data, $adminId, 'upload:' . ($uploaded['name'] ?? 'file.json'));
    }

    /**
     * @param array<string, mixed> $data
     * @return array{ok: bool, message: string, imported: int, skipped: int}
     */
    public static function importFromData(PDO $conn, array $data, int $adminId, string $source = ''): array
    {
        if (!isset($data['suggestions']) || !is_array($data['suggestions'])) {
            return [
                'ok' => false,
                'message' => 'Không thể import taxonomy suggestions. Thiếu mảng suggestions.',
                'imported' => 0,
                'skipped' => 0,
            ];
        }

        $imported = 0;
        $skipped = 0;

        try {
            $conn->beginTransaction();

            foreach ($data['suggestions'] as $item) {
                if (!is_array($item)) {
                    $skipped++;
                    continue;
                }

                $suggestionId = trim((string) ($item['suggestion_id'] ?? ''));
                $canonical = trim((string) ($item['suggested_canonical_name'] ?? ''));
                if ($suggestionId === '' || $canonical === '') {
                    $skipped++;
                    continue;
                }

                $fields = self::normalizeSuggestionFields($item);
                $existing = AiTaxonomyRepository::findSuggestionBySuggestionId($conn, $suggestionId);

                if ($existing !== null && in_array((string) $existing['status'], self::REVIEWED_STATUSES, true)) {
                    $skipped++;
                    continue;
                }

                if ($existing === null) {
                    AiTaxonomyRepository::insertSuggestion($conn, $fields);
                } else {
                    AiTaxonomyRepository::updatePendingSuggestion($conn, (int) $existing['id'], $fields);
                }
                $imported++;
            }

            AiTaxonomyRepository::insertAuditLog(
                $conn,
                'import_suggestions',
                null,
                null,
                null,
                ['source' => $source, 'imported' => $imported, 'skipped' => $skipped, 'version' => $data['version'] ?? null],
                $adminId
            );

            $conn->commit();
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log('ai_taxonomy import error: ' . $e->getMessage());

            return [
                'ok' => false,
                'message' => 'Không thể import taxonomy suggestions. Vui lòng kiểm tra file JSON hoặc thử lại.',
                'imported' => 0,
                'skipped' => 0,
            ];
        }

        return [
            'ok' => true,
            'message' => "Import xong: {$imported} suggestion, bỏ qua {$skipped}.",
            'imported' => $imported,
            'skipped' => $skipped,
        ];
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private static function normalizeSuggestionFields(array $item): array
    {
        return [
            'suggestion_id' => trim((string) ($item['suggestion_id'] ?? '')),
            'suggested_canonical_name' => trim((string) ($item['suggested_canonical_name'] ?? '')),
            'suggested_category' => trim((string) ($item['suggested_category'] ?? '')) ?: null,
            'suggested_aliases_json' => json_encode($item['suggested_aliases'] ?? [], JSON_UNESCAPED_UNICODE),
            'frequency' => (int) ($item['frequency'] ?? 0),
            'confidence' => isset($item['confidence']) ? (float) $item['confidence'] : null,
            'nearest_existing_skills_json' => json_encode($item['nearest_existing_skills'] ?? [], JSON_UNESCAPED_UNICODE),
            'example_contexts_json' => json_encode($item['example_contexts'] ?? [], JSON_UNESCAPED_UNICODE),
            'example_evidence_json' => json_encode($item['example_evidence'] ?? [], JSON_UNESCAPED_UNICODE),
            'raw_json' => json_encode($item, JSON_UNESCAPED_UNICODE),
            'status' => 'pending_review',
        ];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public static function approveNewSkill(
        PDO $conn,
        int $suggestionDbId,
        int $adminId,
        string $skillName,
        string $category,
        array $aliases,
        array $related = [],
        array $transferable = [],
        bool $autoExport = true
    ): array {
        $suggestion = AiTaxonomyRepository::findSuggestionById($conn, $suggestionDbId);
        if (!$suggestion || (string) $suggestion['status'] !== 'pending_review') {
            return ['ok' => false, 'message' => 'Suggestion không tồn tại hoặc đã được xử lý.'];
        }

        $skillName = trim($skillName);
        $category = trim($category) ?: 'Pending Classification';
        if ($skillName === '') {
            return ['ok' => false, 'message' => 'Tên skill không được để trống.'];
        }

        $aliases = ai_taxonomy_dedupe_aliases($aliases);
        $existing = AiTaxonomyRepository::findCustomSkillByName($conn, $skillName);
        if ($existing !== null && (string) ($existing['source_suggestion_id'] ?? '') !== (string) $suggestion['suggestion_id']) {
            return ['ok' => false, 'message' => 'Skill đã tồn tại trong custom taxonomy.'];
        }

        try {
            $conn->beginTransaction();

            if ($existing === null) {
                AiTaxonomyRepository::insertCustomSkill($conn, [
                    'skill_name' => $skillName,
                    'category' => $category,
                    'aliases_json' => json_encode($aliases, JSON_UNESCAPED_UNICODE),
                    'related_json' => json_encode(array_values($related), JSON_UNESCAPED_UNICODE),
                    'transferable_json' => json_encode(array_values($transferable), JSON_UNESCAPED_UNICODE),
                    'source_suggestion_id' => (string) $suggestion['suggestion_id'],
                    'created_by' => $adminId,
                ]);
            } else {
                AiTaxonomyRepository::updateCustomSkillAliases(
                    $conn,
                    (int) $existing['id'],
                    json_encode($aliases, JSON_UNESCAPED_UNICODE),
                    $category
                );
            }

            $oldStatus = (string) $suggestion['status'];
            AiTaxonomyRepository::updateSuggestionDecision($conn, $suggestionDbId, [
                'status' => 'approved_new_skill',
                'decision_type' => 'approve_new_skill',
                'decision_note' => null,
                'target_skill_name' => $skillName,
                'reviewed_by' => $adminId,
            ]);

            AiTaxonomyRepository::insertAuditLog(
                $conn,
                'approve_new_skill',
                (string) $suggestion['suggestion_id'],
                $oldStatus,
                'approved_new_skill',
                ['skill_name' => $skillName, 'category' => $category, 'aliases' => $aliases],
                $adminId
            );

            $conn->commit();
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log('ai_taxonomy approve_new_skill: ' . $e->getMessage());

            return ['ok' => false, 'message' => 'Không thể lưu skill mới. Vui lòng thử lại.'];
        }

        if ($autoExport) {
            $export = self::exportMergedTaxonomy($conn, $adminId);
            if (!$export['ok']) {
                return ['ok' => true, 'message' => 'Đã duyệt skill mới nhưng export merged taxonomy thất bại: ' . $export['message']];
            }
        }

        return ['ok' => true, 'message' => 'Đã duyệt skill mới và cập nhật custom taxonomy.'];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public static function addAliasesToExisting(
        PDO $conn,
        int $suggestionDbId,
        int $adminId,
        string $targetSkillName,
        array $aliases,
        string $decisionType = 'add_alias_to_existing',
        string $newStatus = 'approved_alias',
        bool $autoExport = true
    ): array {
        $suggestion = AiTaxonomyRepository::findSuggestionById($conn, $suggestionDbId);
        if (!$suggestion || (string) $suggestion['status'] !== 'pending_review') {
            return ['ok' => false, 'message' => 'Suggestion không tồn tại hoặc đã được xử lý.'];
        }

        $targetSkillName = trim($targetSkillName);
        if ($targetSkillName === '') {
            return ['ok' => false, 'message' => 'Chọn skill đích để thêm alias.'];
        }

        $aliases = ai_taxonomy_dedupe_aliases($aliases);
        if ($aliases === []) {
            $decoded = json_decode((string) ($suggestion['suggested_aliases_json'] ?? '[]'), true);
            $aliases = is_array($decoded) ? ai_taxonomy_dedupe_aliases(array_map('strval', $decoded)) : [];
        }
        if ($aliases === []) {
            return ['ok' => false, 'message' => 'Cần ít nhất một alias.'];
        }

        $base = self::loadBaseTaxonomy();
        $isBaseSkill = array_key_exists($targetSkillName, $base);

        try {
            $conn->beginTransaction();

            $custom = AiTaxonomyRepository::findCustomSkillByName($conn, $targetSkillName);
            if ($custom !== null) {
                $existingAliases = json_decode((string) ($custom['aliases_json'] ?? '[]'), true);
                $existingAliases = is_array($existingAliases) ? $existingAliases : [];
                $mergedAliases = ai_taxonomy_dedupe_aliases(array_merge($existingAliases, $aliases));
                AiTaxonomyRepository::updateCustomSkillAliases(
                    $conn,
                    (int) $custom['id'],
                    json_encode($mergedAliases, JSON_UNESCAPED_UNICODE)
                );
            } elseif ($isBaseSkill) {
                $baseEntry = $base[$targetSkillName];
                $baseAliases = is_array($baseEntry['aliases'] ?? null) ? $baseEntry['aliases'] : [];
                $mergedAliases = ai_taxonomy_dedupe_aliases(array_merge($baseAliases, $aliases));
                AiTaxonomyRepository::insertCustomSkill($conn, [
                    'skill_name' => $targetSkillName,
                    'category' => (string) ($baseEntry['category'] ?? 'Pending Classification'),
                    'aliases_json' => json_encode($mergedAliases, JSON_UNESCAPED_UNICODE),
                    'related_json' => json_encode($baseEntry['related'] ?? [], JSON_UNESCAPED_UNICODE),
                    'transferable_json' => json_encode($baseEntry['transferable'] ?? [], JSON_UNESCAPED_UNICODE),
                    'source_suggestion_id' => (string) $suggestion['suggestion_id'],
                    'created_by' => $adminId,
                ]);
            } else {
                $conn->rollBack();

                return ['ok' => false, 'message' => 'Skill đích không tồn tại trong taxonomy.'];
            }

            $oldStatus = (string) $suggestion['status'];
            AiTaxonomyRepository::updateSuggestionDecision($conn, $suggestionDbId, [
                'status' => $newStatus,
                'decision_type' => $decisionType,
                'decision_note' => null,
                'target_skill_name' => $targetSkillName,
                'reviewed_by' => $adminId,
            ]);

            AiTaxonomyRepository::insertAuditLog(
                $conn,
                $decisionType,
                (string) $suggestion['suggestion_id'],
                $oldStatus,
                $newStatus,
                ['target_skill_name' => $targetSkillName, 'aliases' => $aliases],
                $adminId
            );

            $conn->commit();
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log('ai_taxonomy add_alias: ' . $e->getMessage());

            return ['ok' => false, 'message' => 'Không thể thêm alias. Vui lòng thử lại.'];
        }

        if ($autoExport) {
            $export = self::exportMergedTaxonomy($conn, $adminId);
            if (!$export['ok']) {
                return ['ok' => true, 'message' => 'Đã lưu alias nhưng export merged taxonomy thất bại: ' . $export['message']];
            }
        }

        return ['ok' => true, 'message' => 'Đã thêm alias vào skill đích.'];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public static function rejectSuggestion(
        PDO $conn,
        int $suggestionDbId,
        int $adminId,
        string $note = ''
    ): array {
        $suggestion = AiTaxonomyRepository::findSuggestionById($conn, $suggestionDbId);
        if (!$suggestion || (string) $suggestion['status'] !== 'pending_review') {
            return ['ok' => false, 'message' => 'Suggestion không tồn tại hoặc đã được xử lý.'];
        }

        $oldStatus = (string) $suggestion['status'];
        AiTaxonomyRepository::updateSuggestionDecision($conn, $suggestionDbId, [
            'status' => 'rejected',
            'decision_type' => 'reject',
            'decision_note' => trim($note) ?: null,
            'target_skill_name' => null,
            'reviewed_by' => $adminId,
        ]);

        AiTaxonomyRepository::insertAuditLog(
            $conn,
            'reject',
            (string) $suggestion['suggestion_id'],
            $oldStatus,
            'rejected',
            ['note' => trim($note)],
            $adminId
        );

        return ['ok' => true, 'message' => 'Đã từ chối suggestion.'];
    }

    /**
     * @return array<string, mixed>
     */
    public static function loadBaseTaxonomy(): array
    {
        $cfg = ai_taxonomy_config();
        $path = trim((string) ($cfg['base_taxonomy_path'] ?? ''));
        if ($path === '' || !is_file($path)) {
            return [];
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            return [];
        }

        $data = json_decode($raw, true);

        return is_array($data) ? $data : [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function loadCustomTaxonomySkills(PDO $conn): array
    {
        return AiTaxonomyRepository::listActiveCustomSkills($conn);
    }

    /**
     * @param array<string, mixed> $base
     * @param list<array<string, mixed>> $customRows
     * @return array<string, mixed>
     */
    public static function mergeTaxonomy(array $base, array $customRows): array
    {
        $merged = $base;

        foreach ($customRows as $row) {
            $skillName = trim((string) ($row['skill_name'] ?? ''));
            if ($skillName === '') {
                continue;
            }

            $aliases = json_decode((string) ($row['aliases_json'] ?? '[]'), true);
            $aliases = is_array($aliases) ? ai_taxonomy_dedupe_aliases(array_map('strval', $aliases)) : [];
            $related = json_decode((string) ($row['related_json'] ?? '[]'), true);
            $related = is_array($related) ? array_values($related) : [];
            $transferable = json_decode((string) ($row['transferable_json'] ?? '[]'), true);
            $transferable = is_array($transferable) ? array_values($transferable) : [];
            $category = trim((string) ($row['category'] ?? '')) ?: 'Pending Classification';

            if (isset($merged[$skillName]) && is_array($merged[$skillName])) {
                $existing = $merged[$skillName];
                $existingAliases = is_array($existing['aliases'] ?? null) ? $existing['aliases'] : [];
                $merged[$skillName] = [
                    'aliases' => ai_taxonomy_dedupe_aliases(array_merge($existingAliases, $aliases)),
                    'category' => $category !== 'Pending Classification'
                        ? $category
                        : (string) ($existing['category'] ?? 'Pending Classification'),
                    'related' => $related !== [] ? $related : (is_array($existing['related'] ?? null) ? $existing['related'] : []),
                    'transferable' => $transferable !== [] ? $transferable : (is_array($existing['transferable'] ?? null) ? $existing['transferable'] : []),
                ];
            } else {
                $merged[$skillName] = [
                    'aliases' => $aliases,
                    'category' => $category,
                    'related' => $related,
                    'transferable' => $transferable,
                ];
            }
        }

        ksort($merged, SORT_NATURAL | SORT_FLAG_CASE);

        return $merged;
    }

    /**
     * @return array{ok: bool, message: string, path: string, skill_count: int}
     */
    public static function exportMergedTaxonomy(PDO $conn, int $adminId): array
    {
        $cfg = ai_taxonomy_config();
        $mergedPath = trim((string) ($cfg['merged_taxonomy_path'] ?? ''));
        if ($mergedPath === '') {
            return ['ok' => false, 'message' => 'Chưa cấu hình merged_taxonomy_path.', 'path' => '', 'skill_count' => 0];
        }

        $base = self::loadBaseTaxonomy();
        if ($base === []) {
            return ['ok' => false, 'message' => 'Không đọc được base taxonomy.', 'path' => '', 'skill_count' => 0];
        }

        $custom = self::loadCustomTaxonomySkills($conn);
        $merged = self::mergeTaxonomy($base, $custom);
        $json = json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return ['ok' => false, 'message' => 'Không encode được merged taxonomy.', 'path' => '', 'skill_count' => 0];
        }

        $dir = dirname($mergedPath);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return ['ok' => false, 'message' => 'Không tạo được thư mục taxonomy runtime.', 'path' => '', 'skill_count' => 0];
        }

        $tmpPath = $dir . DIRECTORY_SEPARATOR . 'skills_merged.tmp.json';
        if (file_put_contents($tmpPath, $json) === false) {
            return ['ok' => false, 'message' => 'Không ghi file taxonomy tạm.', 'path' => '', 'skill_count' => 0];
        }

        $check = json_decode((string) file_get_contents($tmpPath), true);
        if (!is_array($check)) {
            @unlink($tmpPath);

            return ['ok' => false, 'message' => 'JSON vừa ghi không hợp lệ.', 'path' => '', 'skill_count' => 0];
        }

        if (is_file($mergedPath) && !@unlink($mergedPath)) {
            @unlink($tmpPath);

            return ['ok' => false, 'message' => 'Không ghi đè skills_merged.json (permission denied).', 'path' => '', 'skill_count' => 0];
        }

        if (!@rename($tmpPath, $mergedPath)) {
            if (!copy($tmpPath, $mergedPath)) {
                @unlink($tmpPath);

                return ['ok' => false, 'message' => 'Không thay thế skills_merged.json.', 'path' => '', 'skill_count' => 0];
            }
            @unlink($tmpPath);
        }

        AiTaxonomyRepository::insertAuditLog(
            $conn,
            'export_merged_taxonomy',
            null,
            null,
            null,
            ['path' => $mergedPath, 'skill_count' => count($merged)],
            $adminId
        );

        return [
            'ok' => true,
            'message' => 'Đã export ' . count($merged) . ' skills vào skills_merged.json.',
            'path' => $mergedPath,
            'skill_count' => count($merged),
        ];
    }

    /**
     * @return list<string>
     */
    public static function listSkillNamesForSelect(PDO $conn): array
    {
        $names = array_keys(self::loadBaseTaxonomy());
        foreach (self::loadCustomTaxonomySkills($conn) as $row) {
            $name = trim((string) ($row['skill_name'] ?? ''));
            if ($name !== '') {
                $names[] = $name;
            }
        }

        $names = array_values(array_unique($names));
        sort($names, SORT_NATURAL | SORT_FLAG_CASE);

        return $names;
    }

    /**
     * @return list<string>
     */
    public static function decodeJsonList(?string $json): array
    {
        if ($json === null || trim($json) === '') {
            return [];
        }
        $decoded = json_decode($json, true);

        return is_array($decoded) ? array_values(array_map('strval', $decoded)) : [];
    }
}
