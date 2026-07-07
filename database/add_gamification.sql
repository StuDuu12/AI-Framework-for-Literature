-- Gamification: badges, streaks, leaderboard

-- Badge definitions
CREATE TABLE IF NOT EXISTS `badge` (
  `badge_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `icon` varchar(50) DEFAULT 'bi-award',
  `color` varchar(20) DEFAULT 'primary',
  `criteria_type` varchar(50) NOT NULL COMMENT 'e.g., tasks_completed, evaluations_done, streak_days',
  `criteria_value` int NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`badge_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User badges (earned)
CREATE TABLE IF NOT EXISTS `user_badge` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `badge_id` int NOT NULL,
  `earned_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_badge` (`user_id`, `badge_id`),
  KEY `idx_ub_user` (`user_id`),
  KEY `idx_ub_badge` (`badge_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Daily streaks
CREATE TABLE IF NOT EXISTS `user_streak` (
  `user_id` int NOT NULL,
  `current_streak` int NOT NULL DEFAULT 0,
  `longest_streak` int NOT NULL DEFAULT 0,
  `last_active_date` date DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Streak log
CREATE TABLE IF NOT EXISTS `streak_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `log_date` date NOT NULL,
  `activity_type` varchar(50) DEFAULT 'login',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_streak_log` (`user_id`, `log_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Points system
CREATE TABLE IF NOT EXISTS `user_points` (
  `user_id` int NOT NULL,
  `total_points` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Points log
CREATE TABLE IF NOT EXISTS `points_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `points` int NOT NULL,
  `reason` varchar(200) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pl_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default badges
INSERT INTO `badge` (`name`, `description`, `icon`, `color`, `criteria_type`, `criteria_value`) VALUES
('First Steps', 'Complete your first task', 'bi-check-circle', 'success', 'tasks_completed', 1),
('Task Master', 'Complete 5 tasks', 'bi-award', 'primary', 'tasks_completed', 5),
('Task Legend', 'Complete 10 tasks', 'bi-trophy', 'warning', 'tasks_completed', 10),
('Peer Reviewer', 'Complete your first evaluation', 'bi-star', 'info', 'evaluations_done', 1),
('Expert Reviewer', 'Complete 5 evaluations', 'bi-star-fill', 'primary', 'evaluations_done', 5),
('3-Day Streak', 'Login 3 days in a row', 'bi-fire', 'danger', 'streak_days', 3),
('7-Day Streak', 'Login 7 days in a row', 'bi-fire', 'warning', 'streak_days', 7),
('30-Day Streak', 'Login 30 days in a row', 'bi-fire', 'success', 'streak_days', 30);
