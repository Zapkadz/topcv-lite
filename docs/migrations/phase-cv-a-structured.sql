-- Phase CV-A — CV structured (core tables)
-- Chạy qua migrate-phase-cv-a.php

CREATE TABLE IF NOT EXISTS `cv_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `candidate_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL DEFAULT '',
  `target_position` varchar(255) NOT NULL DEFAULT '',
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `phone` varchar(10) DEFAULT NULL COMMENT 'VN mobile: 0xxxxxxxxx',
  `email` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `avatar_path` varchar(255) DEFAULT NULL,
  `career_objective` text DEFAULT NULL,
  `interests` text DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `template_key` varchar(32) NOT NULL DEFAULT 'classic',
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `completion_percent` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cv_profiles_candidate` (`candidate_id`),
  KEY `idx_cv_profiles_primary` (`candidate_id`,`is_primary`),
  CONSTRAINT `cv_profiles_candidate_fk` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `cv_educations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cv_id` int(11) NOT NULL,
  `start_date` char(7) DEFAULT NULL COMMENT 'YYYY-MM',
  `end_date` char(7) DEFAULT NULL COMMENT 'YYYY-MM',
  `school_name` varchar(255) NOT NULL DEFAULT '',
  `major` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_cv_educations_cv` (`cv_id`,`sort_order`),
  CONSTRAINT `cv_educations_cv_fk` FOREIGN KEY (`cv_id`) REFERENCES `cv_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `cv_experiences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cv_id` int(11) NOT NULL,
  `start_date` char(7) DEFAULT NULL COMMENT 'YYYY-MM',
  `end_date` char(7) DEFAULT NULL COMMENT 'YYYY-MM',
  `company_name` varchar(255) NOT NULL DEFAULT '',
  `position` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_cv_experiences_cv` (`cv_id`,`sort_order`),
  CONSTRAINT `cv_experiences_cv_fk` FOREIGN KEY (`cv_id`) REFERENCES `cv_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `cv_skills` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cv_id` int(11) NOT NULL,
  `skill_name` varchar(255) NOT NULL DEFAULT '',
  `description` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_cv_skills_cv` (`cv_id`,`sort_order`),
  CONSTRAINT `cv_skills_cv_fk` FOREIGN KEY (`cv_id`) REFERENCES `cv_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
