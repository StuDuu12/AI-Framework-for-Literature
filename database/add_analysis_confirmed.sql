-- Mark which analysis history version is the student's confirmed final submission
ALTER TABLE `analysis_history`
  ADD COLUMN `is_confirmed` TINYINT(1) NOT NULL DEFAULT 0 AFTER `analysis_data`,
  ADD COLUMN `confirmed_at` TIMESTAMP NULL DEFAULT NULL AFTER `is_confirmed`;
