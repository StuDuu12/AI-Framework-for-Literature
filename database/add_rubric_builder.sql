-- Rubric Builder: add template and level support

-- Rubric templates (reusable across classes)
CREATE TABLE IF NOT EXISTS `rubric_template` (
  `template_id` int NOT NULL AUTO_INCREMENT,
  `class_id` int DEFAULT NULL COMMENT 'NULL = global template available to all classes',
  `name` varchar(200) NOT NULL,
  `description` text,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`template_id`),
  KEY `idx_rubric_class` (`class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Performance levels for each criterion (e.g., Excellent, Good, Fair, Poor)
CREATE TABLE IF NOT EXISTS `rubric_level` (
  `level_id` int NOT NULL AUTO_INCREMENT,
  `tc_id` int NOT NULL COMMENT 'FK to tieu_chi',
  `level_name` varchar(100) NOT NULL COMMENT 'e.g., Excellent, Good, Fair, Poor',
  `score` int NOT NULL COMMENT 'Score for this level',
  `description` text COMMENT 'Description of what this level looks like',
  `sort_order` int DEFAULT 0,
  PRIMARY KEY (`level_id`),
  KEY `idx_rubric_level_tc` (`tc_id`),
  CONSTRAINT `fk_rubric_level_tc` FOREIGN KEY (`tc_id`) REFERENCES `tieu_chi` (`tc_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Link rubric templates to criteria groups
ALTER TABLE `nhom_tc`
ADD COLUMN `template_id` int DEFAULT NULL AFTER `description`,
ADD KEY `idx_nhom_tc_template` (`template_id`);
