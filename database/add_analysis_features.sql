-- Analysis draft (autosave) and version history
CREATE TABLE IF NOT EXISTS `analysis_draft` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `task_id` int NOT NULL,
  `deliver_id` int NOT NULL,
  `draft_data` json NOT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_analysis_draft` (`user_id`,`task_id`,`deliver_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `analysis_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `task_id` int NOT NULL,
  `deliver_id` int NOT NULL,
  `label` varchar(255) DEFAULT NULL,
  `analysis_data` json NOT NULL,
  `is_confirmed` TINYINT(1) NOT NULL DEFAULT 0,
  `confirmed_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_analysis_history_lookup` (`user_id`,`task_id`,`deliver_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
