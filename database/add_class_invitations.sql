-- ============================================================
-- Migration: Class invitations + Task deadlines
-- Áp dụng: mysql -u root -p diemdanh_literature < database/add_class_invitations.sql
-- ============================================================

-- 1. Bảng mã mời lớp học
CREATE TABLE IF NOT EXISTS `class_invitations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class_id` int NOT NULL,
  `code` varchar(12) NOT NULL,
  `created_by` int NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `max_uses` int DEFAULT NULL,
  `use_count` int DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inv_code` (`code`),
  KEY `idx_inv_class` (`class_id`),
  CONSTRAINT `fk_inv_class` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inv_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Mã mời lớp học';

-- 2. Thêm cột deadline cho task và deliver
ALTER TABLE `task`
  ADD COLUMN `deadline` datetime NULL DEFAULT NULL AFTER `description`;

ALTER TABLE `deliver`
  ADD COLUMN `deadline` datetime NULL DEFAULT NULL AFTER `status`,
  ADD COLUMN `submitted_at` datetime NULL DEFAULT NULL AFTER `deadline`;
