-- Supplemental seed for PHASE_CHAMDIEM (run after main import, or use: php seed_grading_phase.php)
-- Binds grading tasks to rubrics + sample peer/teacher scores for topic "Story" (task 41 / source 36)

INSERT IGNORE INTO `task_evaluate` (`id_task`, `id_group_tc`) VALUES
(19, 1),
(30, 1),
(41, 3);

-- Ensure submitted delivers on source task 36
UPDATE `deliver` SET `status` = 2 WHERE `task_id` = 36 AND `response` IS NOT NULL;
