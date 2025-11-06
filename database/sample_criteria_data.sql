-- Sample data for testing peer evaluation system
-- Run this after setting up the main database

-- Sample criteria group for class 2 (Demo class)
INSERT INTO nhom_tc (nhom_tc_id, class_id, ten_nhom_tc, description) VALUES
(1, 2, 'Bộ tiêu chí phân tích văn học', 'Đánh giá toàn diện về phân tích văn bản'),
(2, 2, 'Bộ tiêu chí viết bài luận', 'Đánh giá kỹ năng viết luận văn'),
(3, 1, 'Bộ tiêu chí phân tích thơ', 'Đánh giá chuyên sâu về phân tích tác phẩm thơ');

-- Sample criteria for group 1 (Phân tích văn học)
INSERT INTO tieu_chi (tc_id, name_tc, nhom_tc_id, description, min_score, max_score) VALUES
(1, 'Nội dung phân tích', 1, 'Đánh giá độ sâu, tính chính xác và sự hiểu biết về tác phẩm', 0, 10),
(2, 'Ngôn ngữ và diễn đạt', 1, 'Đánh giá khả năng sử dụng ngôn ngữ, từ vựng và cách diễn đạt ý tưởng', 0, 10),
(3, 'Cấu trúc và trình bày', 1, 'Đánh giá tính logic, mạch lạc và cách tổ chức bài viết', 0, 10),
(4, 'Sáng tạo và tư duy', 1, 'Đánh giá khả năng tư duy phản biện và góc nhìn sáng tạo', 0, 10),
(5, 'Dẫn chứng và minh họa', 1, 'Đánh giá việc sử dụng dẫn chứng từ tác phẩm để chứng minh luận điểm', 0, 10);

-- Sample criteria for group 2 (Viết bài luận)
INSERT INTO tieu_chi (tc_id, name_tc, nhom_tc_id, description, min_score, max_score) VALUES
(6, 'Luận điểm và luận cứ', 2, 'Đánh giá tính rõ ràng của luận điểm và sức thuyết phục của luận cứ', 0, 10),
(7, 'Mở bài và kết bài', 2, 'Đánh giá cách mở đầu và kết thúc bài viết', 0, 10),
(8, 'Chính tả và ngữ pháp', 2, 'Đánh giá độ chính xác về chính tả, dấu câu và ngữ pháp', 0, 10);

-- Sample criteria for group 3 (Phân tích thơ)
INSERT INTO tieu_chi (tc_id, name_tc, nhom_tc_id, description, min_score, max_score) VALUES
(9, 'Phân tích nội dung', 3, 'Hiểu và phân tích chủ đề, tư tưởng của bài thơ', 0, 10),
(10, 'Phân tích nghệ thuật', 3, 'Phân tích hình ảnh, biện pháp tu từ, ngôn ngữ thơ', 0, 10),
(11, 'Giá trị tác phẩm', 3, 'Đánh giá khả năng nhận xét về giá trị và ý nghĩa của tác phẩm', 0, 10);

-- Note: No need to insert sample task_evaluate or deliver_evaluate
-- These will be created through the web interface
