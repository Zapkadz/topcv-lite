-- Phase 2B — Soft delete cho bảng jobs
-- Windows: php docs/migrations/run-phase-2b-job-soft-delete.php
-- Lưu ý: migrate-phase-2b.php không parse file này bằng split thô; dùng logic PHP.

ALTER TABLE `jobs`
  ADD COLUMN `deleted_at` datetime NULL DEFAULT NULL;

CREATE INDEX `idx_jobs_deleted_at` ON `jobs` (`deleted_at`);
