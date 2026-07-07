-- Notifications and app settings (AI config)
CREATE TABLE IF NOT EXISTS `user_notification` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `type` varchar(32) NOT NULL DEFAULT 'info',
  `title` varchar(255) NOT NULL,
  `message` text,
  `link` varchar(512) DEFAULT NULL,
  `is_read` tinyint NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`,`is_read`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `app_settings` (
  `setting_key` varchar(64) NOT NULL,
  `setting_value` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `app_settings` (`setting_key`, `setting_value`) VALUES
('gemini_model', 'gemini-2.5-pro'),
('gemini_temperature', '0.7'),
('gemini_max_tokens', '4000'),
('ai_enabled', '1')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
