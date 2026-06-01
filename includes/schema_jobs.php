<?php

if (!function_exists('jobs_schema_has_soft_delete')) {
    function jobs_schema_has_soft_delete(PDO $conn): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        try {
            $stmt = $conn->query("SHOW COLUMNS FROM jobs LIKE 'deleted_at'");
            $cached = (bool) $stmt->fetch();
        } catch (Throwable $e) {
            $cached = false;
        }

        return $cached;
    }
}
