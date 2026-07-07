-- Add anonymous evaluation support
-- Adds is_anonymous flag to the setting table (per-task evaluation config)

ALTER TABLE `setting`
ADD COLUMN `is_anonymous` tinyint(1) NOT NULL DEFAULT 0 AFTER `end_time`;
