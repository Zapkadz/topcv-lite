-- Phase EMP-B prep — plain text CV lúc apply (cho AI screening)
-- Chạy qua migrate-phase-emp-b-cv-snapshot-text.php

ALTER TABLE `applications`
  ADD COLUMN `cv_snapshot_text` longtext DEFAULT NULL
  COMMENT 'Plain text CV bất biến lúc apply, cho AI screening'
  AFTER `cv_snapshot_json`;
