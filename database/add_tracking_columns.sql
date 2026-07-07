-- Add tracking columns to response table for user behavior tracking
-- Run this migration to add support for edit/copy tracking

ALTER TABLE `response` 
ADD COLUMN `tracking_data` JSON NULL COMMENT 'User behavior tracking data (copy, edit, paste events)' AFTER `json_ai`,
ADD COLUMN `tracking_updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last tracking data update timestamp' AFTER `tracking_data`;

-- Create index for efficient queries on tracking data
ALTER TABLE `response` ADD INDEX `idx_tracking_updated` (`tracking_updated_at`);

-- Optional: View to get tracking summary
-- CREATE VIEW tracking_summary AS
-- SELECT 
--     r.id_response,
--     r.task_id,
--     u.fullname,
--     JSON_EXTRACT(r.tracking_data, '$.globalSummary.totalCopyEvents') as copy_events,
--     JSON_EXTRACT(r.tracking_data, '$.globalSummary.totalCopyPercent') as copy_percent,
--     JSON_EXTRACT(r.tracking_data, '$.globalSummary.averageEditChangePercent') as edit_percent,
--     r.tracking_updated_at
-- FROM response r
-- JOIN users u ON r.owner_user_id = u.user_id
-- WHERE r.tracking_data IS NOT NULL;
