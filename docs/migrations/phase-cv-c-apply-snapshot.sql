-- Phase CV-C — applications: CV structured snapshot khi apply
-- Chạy qua migrate-phase-cv-c.php (dùng _cv-c-migrate-steps.php, không split file này)

ALTER TABLE `applications`
  ADD COLUMN `cv_profile_id` int(11) DEFAULT NULL COMMENT 'FK cv_profiles lúc apply' AFTER `candidate_id`,
  ADD COLUMN `cv_snapshot_json` longtext DEFAULT NULL COMMENT 'Bản CV structured bất biến' AFTER `cv_snapshot`;

ALTER TABLE `applications`
  MODIFY COLUMN `cv_snapshot` varchar(255) DEFAULT NULL COMMENT 'File PDF legacy; apply mới dùng JSON';

ALTER TABLE `applications`
  ADD KEY `idx_applications_cv_profile` (`cv_profile_id`);

ALTER TABLE `applications`
  ADD CONSTRAINT `applications_cv_profile_fk`
    FOREIGN KEY (`cv_profile_id`) REFERENCES `cv_profiles` (`id`) ON DELETE SET NULL;
