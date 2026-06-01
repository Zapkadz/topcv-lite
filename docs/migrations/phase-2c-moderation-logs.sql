-- Phase 2C — Bảng moderation_logs
-- Chạy qua migrate-phase-2c.php (khuyến nghị)

CREATE TABLE IF NOT EXISTS `moderation_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `entity_type` enum('job','employer') NOT NULL,
  `entity_id` int(11) NOT NULL,
  `action` enum('approve','reject') NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_moderation_entity` (`entity_type`,`entity_id`),
  KEY `idx_moderation_created` (`created_at`),
  KEY `idx_moderation_admin` (`admin_id`),
  CONSTRAINT `moderation_logs_admin_fk` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
