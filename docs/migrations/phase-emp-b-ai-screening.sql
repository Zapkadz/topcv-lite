-- Phase EMP-B — bảng lưu kết quả AI screening theo application
-- Chạy qua migrate-phase-emp-b-ai-screening.php

CREATE TABLE IF NOT EXISTS `ai_screening_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `candidate_id` int(11) DEFAULT NULL,
  `ai_rank` int(11) DEFAULT NULL,
  `final_score` int(11) DEFAULT NULL,
  `recommendation` varchar(50) DEFAULT NULL,
  `scores_json` longtext DEFAULT NULL,
  `review_card_json` longtext DEFAULT NULL,
  `raw_result_json` longtext DEFAULT NULL,
  `run_id` varchar(64) DEFAULT NULL COMMENT 'run-{timestamp}',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ai_screening_job_application` (`job_id`,`application_id`),
  KEY `idx_ai_screening_job` (`job_id`),
  KEY `idx_ai_screening_application` (`application_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
