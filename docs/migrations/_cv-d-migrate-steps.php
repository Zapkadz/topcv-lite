<?php

/** @var list<string> $cv_d_migration_steps */
$cv_d_migration_steps = [
    "CREATE TABLE IF NOT EXISTS `cv_activities` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `cv_id` int(11) NOT NULL,
      `start_date` char(7) DEFAULT NULL,
      `end_date` char(7) DEFAULT NULL,
      `organization` varchar(255) NOT NULL DEFAULT '',
      `role` varchar(255) DEFAULT NULL,
      `description` text DEFAULT NULL,
      `sort_order` int(11) NOT NULL DEFAULT 0,
      PRIMARY KEY (`id`),
      KEY `idx_cv_activities_cv` (`cv_id`,`sort_order`),
      CONSTRAINT `cv_activities_cv_fk` FOREIGN KEY (`cv_id`) REFERENCES `cv_profiles` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
    "CREATE TABLE IF NOT EXISTS `cv_certificates` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `cv_id` int(11) NOT NULL,
      `issued_at` char(7) DEFAULT NULL,
      `certificate_name` varchar(255) NOT NULL DEFAULT '',
      `sort_order` int(11) NOT NULL DEFAULT 0,
      PRIMARY KEY (`id`),
      KEY `idx_cv_certificates_cv` (`cv_id`,`sort_order`),
      CONSTRAINT `cv_certificates_cv_fk` FOREIGN KEY (`cv_id`) REFERENCES `cv_profiles` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
    "CREATE TABLE IF NOT EXISTS `cv_awards` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `cv_id` int(11) NOT NULL,
      `awarded_at` char(7) DEFAULT NULL,
      `title` varchar(255) NOT NULL DEFAULT '',
      `description` text DEFAULT NULL,
      `sort_order` int(11) NOT NULL DEFAULT 0,
      PRIMARY KEY (`id`),
      KEY `idx_cv_awards_cv` (`cv_id`,`sort_order`),
      CONSTRAINT `cv_awards_cv_fk` FOREIGN KEY (`cv_id`) REFERENCES `cv_profiles` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
    "CREATE TABLE IF NOT EXISTS `cv_references` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `cv_id` int(11) NOT NULL,
      `full_name` varchar(255) NOT NULL DEFAULT '',
      `position` varchar(255) DEFAULT NULL,
      `contact_info` varchar(255) DEFAULT NULL,
      `sort_order` int(11) NOT NULL DEFAULT 0,
      PRIMARY KEY (`id`),
      KEY `idx_cv_references_cv` (`cv_id`,`sort_order`),
      CONSTRAINT `cv_references_cv_fk` FOREIGN KEY (`cv_id`) REFERENCES `cv_profiles` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
];
