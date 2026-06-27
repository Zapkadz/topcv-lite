<?php
declare(strict_types=1);

class AiScreeningRepository
{
    /**
     * @param array<string, mixed> $row
     */
    public static function upsert(PDO $conn, array $row): bool
    {
        $stmt = $conn->prepare(
            'INSERT INTO ai_screening_results
                (job_id, application_id, candidate_id, ai_rank, final_score, recommendation,
                 scores_json, review_card_json, raw_result_json, run_id, updated_at)
             VALUES
                (:job_id, :application_id, :candidate_id, :ai_rank, :final_score, :recommendation,
                 :scores_json, :review_card_json, :raw_result_json, :run_id, NOW())
             ON DUPLICATE KEY UPDATE
                candidate_id = VALUES(candidate_id),
                ai_rank = VALUES(ai_rank),
                final_score = VALUES(final_score),
                recommendation = VALUES(recommendation),
                scores_json = VALUES(scores_json),
                review_card_json = VALUES(review_card_json),
                raw_result_json = VALUES(raw_result_json),
                run_id = VALUES(run_id),
                updated_at = NOW()'
        );

        return $stmt->execute([
            ':job_id' => (int) ($row['job_id'] ?? 0),
            ':application_id' => (int) ($row['application_id'] ?? 0),
            ':candidate_id' => isset($row['candidate_id']) ? (int) $row['candidate_id'] : null,
            ':ai_rank' => isset($row['ai_rank']) ? (int) $row['ai_rank'] : null,
            ':final_score' => isset($row['final_score']) ? (int) $row['final_score'] : null,
            ':recommendation' => $row['recommendation'] ?? null,
            ':scores_json' => $row['scores_json'] ?? null,
            ':review_card_json' => $row['review_card_json'] ?? null,
            ':raw_result_json' => $row['raw_result_json'] ?? null,
            ':run_id' => $row['run_id'] ?? null,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listByJob(PDO $conn, int $jobId): array
    {
        $stmt = $conn->prepare(
            'SELECT * FROM ai_screening_results WHERE job_id = ? ORDER BY ai_rank ASC, final_score DESC'
        );
        $stmt->execute([$jobId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    public static function latestRunIdForJob(PDO $conn, int $jobId): ?string
    {
        $stmt = $conn->prepare(
            'SELECT run_id FROM ai_screening_results
             WHERE job_id = ? AND run_id IS NOT NULL AND run_id != \'\'
             ORDER BY updated_at DESC
             LIMIT 1'
        );
        $stmt->execute([$jobId]);
        $runId = $stmt->fetchColumn();

        return is_string($runId) && $runId !== '' ? $runId : null;
    }

    public static function deleteByJobId(PDO $conn, int $jobId): int
    {
        if ($jobId <= 0) {
            return 0;
        }

        $stmt = $conn->prepare('DELETE FROM ai_screening_results WHERE job_id = ?');
        $stmt->execute([$jobId]);

        return $stmt->rowCount();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listByJobForRun(PDO $conn, int $jobId, string $runId): array
    {
        if ($jobId <= 0 || trim($runId) === '') {
            return [];
        }

        $stmt = $conn->prepare(
            'SELECT * FROM ai_screening_results
             WHERE job_id = ? AND run_id = ?
             ORDER BY ai_rank ASC, final_score DESC'
        );
        $stmt->execute([$jobId, $runId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    /**
     * @return array<int, array<string, mixed>> keyed by application_id
     */
    public static function mapByApplicationForJob(PDO $conn, int $jobId): array
    {
        $latestRunId = self::latestRunIdForJob($conn, $jobId);
        $rows = $latestRunId !== null
            ? self::listByJobForRun($conn, $jobId, $latestRunId)
            : self::listByJob($conn, $jobId);
        $map = [];
        foreach ($rows as $row) {
            $appId = (int) ($row['application_id'] ?? 0);
            if ($appId > 0) {
                $map[$appId] = $row;
            }
        }

        return $map;
    }
}
