<?php

/** @var list<string> $cv_projects_migration_steps */
$cv_projects_migration_steps = [
    "CREATE TABLE IF NOT EXISTS `cv_projects` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `cv_id` int(11) NOT NULL,
      `start_date` char(7) DEFAULT NULL,
      `end_date` char(7) DEFAULT NULL,
      `project_name` varchar(255) NOT NULL DEFAULT '',
      `position` varchar(255) DEFAULT NULL,
      `description` text DEFAULT NULL,
      `sort_order` int(11) NOT NULL DEFAULT 0,
      PRIMARY KEY (`id`),
      KEY `idx_cv_projects_cv` (`cv_id`,`sort_order`),
      CONSTRAINT `cv_projects_cv_fk` FOREIGN KEY (`cv_id`) REFERENCES `cv_profiles` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
];
