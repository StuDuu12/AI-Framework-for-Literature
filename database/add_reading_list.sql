-- Reading List + Bookmark

-- Bookmarks (save tasks for later)
CREATE TABLE IF NOT EXISTS `bookmark` (
  `bookmark_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `task_id` int NOT NULL,
  `note` text COMMENT 'Personal note about this bookmark',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`bookmark_id`),
  UNIQUE KEY `unique_bookmark` (`user_id`, `task_id`),
  KEY `idx_bm_user` (`user_id`),
  KEY `idx_bm_task` (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reading lists (collections of bookmarks)
CREATE TABLE IF NOT EXISTS `reading_list` (
  `list_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`list_id`),
  KEY `idx_rl_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reading list items
CREATE TABLE IF NOT EXISTS `reading_list_item` (
  `id` int NOT NULL AUTO_INCREMENT,
  `list_id` int NOT NULL,
  `task_id` int NOT NULL,
  `added_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_rli` (`list_id`, `task_id`),
  KEY `idx_rli_list` (`list_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
