-- Phase 2D — saved_jobs
-- Chạy qua migrate-phase-2d.php

CREATE TABLE IF NOT EXISTS `saved_jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `candidate_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_saved_job` (`candidate_id`,`job_id`),
  KEY `idx_saved_candidate` (`candidate_id`),
  KEY `idx_saved_job` (`job_id`),
  CONSTRAINT `saved_jobs_candidate_fk` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `saved_jobs_job_fk` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
