-- Admin taxonomy suggestion management (Phase 15 web)

CREATE TABLE IF NOT EXISTS `ai_taxonomy_suggestions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `suggestion_id` varchar(191) NOT NULL,
  `suggested_canonical_name` varchar(255) NOT NULL,
  `suggested_category` varchar(255) DEFAULT NULL,
  `suggested_aliases_json` longtext DEFAULT NULL,
  `frequency` int(11) NOT NULL DEFAULT 0,
  `confidence` decimal(5,4) DEFAULT NULL,
  `nearest_existing_skills_json` longtext DEFAULT NULL,
  `example_contexts_json` longtext DEFAULT NULL,
  `example_evidence_json` longtext DEFAULT NULL,
  `raw_json` longtext DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending_review',
  `decision_type` varchar(50) DEFAULT NULL,
  `decision_note` text DEFAULT NULL,
  `target_skill_name` varchar(255) DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ai_taxonomy_suggestion_id` (`suggestion_id`),
  KEY `idx_ai_taxonomy_suggestions_status` (`status`),
  KEY `idx_ai_taxonomy_suggestions_frequency` (`frequency`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ai_custom_taxonomy_skills` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `skill_name` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'Pending Classification',
  `aliases_json` longtext DEFAULT NULL,
  `related_json` longtext DEFAULT NULL,
  `transferable_json` longtext DEFAULT NULL,
  `source_suggestion_id` varchar(191) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ai_custom_taxonomy_skill_name` (`skill_name`),
  KEY `idx_ai_custom_taxonomy_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ai_taxonomy_audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `suggestion_id` varchar(191) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `payload_json` longtext DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ai_taxonomy_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
