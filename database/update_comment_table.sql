-- Update comment table to ensure reponse_general column exists
-- This column stores the response_id for general comments (when content_id is NULL)

-- Check if column exists, if not add it
ALTER TABLE `comment` 
ADD COLUMN IF NOT EXISTS `reponse_general` INT DEFAULT NULL AFTER `comment_id`;

-- Add comment for documentation
ALTER TABLE `comment` 
MODIFY COLUMN `reponse_general` INT DEFAULT NULL COMMENT 'Response ID for general comments (NULL for content-specific comments)';

-- Update existing general comments to set reponse_general based on task_id
-- This is a one-time migration script
-- You may need to manually set the correct response_id for existing general comments
-- UPDATE comment c
-- LEFT JOIN response r ON c.task_id = r.task_id
-- SET c.reponse_general = r.id_response
-- WHERE c.content_id IS NULL
-- AND c.reponse_general IS NULL;
