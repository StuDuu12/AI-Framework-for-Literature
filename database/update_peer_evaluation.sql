-- SQL Script: Update database for peer evaluation (đánh giá chéo)
-- Date: 2025-11-06

-- Ensure task_evaluate table has correct structure
-- (Should already exist based on SQL dump)

-- Add sample criteria group data (if needed)
-- INSERT INTO nhom_tc (nhom_tc_id, class_id, ten_nhom_tc, description) VALUES
-- (1, 1, 'Bộ tiêu chí phân tích văn học', 'Đánh giá nội dung, ngôn ngữ, trình bày');

-- Add sample criteria (if needed)
-- INSERT INTO tieu_chi (tc_id, name_tc, nhom_tc_id, description, min_score, max_score) VALUES
-- (1, 'Nội dung phân tích', 1, 'Đánh giá độ sâu và chính xác của phân tích', 0, 10),
-- (2, 'Ngôn ngữ và cách diễn đạt', 1, 'Đánh giá khả năng sử dụng ngôn ngữ', 0, 10),
-- (3, 'Trình bày và cấu trúc', 1, 'Đánh giá tính logic và mạch lạc', 0, 10);

-- Note: The deliver_evaluate table structure is correct:
-- - id_deliver: ID của deliver (bài tập được giao cho người chấm)
-- - response_review: ID của response cần được chấm

-- When assigning evaluators:
-- 1. Teacher selects criteria group (nhom_tc) for task → saved in task_evaluate
-- 2. Teacher assigns who evaluates which submission:
--    - System creates a new deliver for the evaluator (if not exists)
--    - System creates deliver_evaluate record linking evaluator's deliver to the response they need to evaluate

-- Example workflow:
-- - Student A (user_id=366) submits homework → creates response_id=10
-- - Teacher assigns Student B (user_id=390) to evaluate Student A's work
-- - System creates deliver for Student B: deliver(task_id=20, type='user', value=390)
-- - System creates deliver_evaluate: deliver_evaluate(id_deliver=StudentB_deliver_id, response_review=10)
-- - Student B now has a task to evaluate response_id=10
