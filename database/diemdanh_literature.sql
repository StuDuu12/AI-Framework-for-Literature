-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 25, 2025 at 11:25 AM
-- Server version: 8.0.36
-- PHP Version: 8.1.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `diemdanh_literature`
--

-- --------------------------------------------------------

--
-- Table structure for table `assignment`
--

CREATE TABLE `assignment` (
  `id_assignment` int NOT NULL,
  `id_setting_detail` int NOT NULL,
  `user_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `id_category` int NOT NULL,
  `name_category` varchar(255) NOT NULL,
  `description` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class`
--

CREATE TABLE `class` (
  `class_id` int NOT NULL,
  `class_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `class`
--

INSERT INTO `class` (`class_id`, `class_name`, `created_at`, `updated_at`) VALUES
(1, 'Lớp 10A1', '2025-10-25 01:15:28', '2025-10-25 01:15:28');

-- --------------------------------------------------------

--
-- Table structure for table `class_member`
--

CREATE TABLE `class_member` (
  `class_id` int NOT NULL,
  `user_id` int NOT NULL,
  `role` enum('admin','teacher','student') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `added_by` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `class_member`
--

INSERT INTO `class_member` (`class_id`, `user_id`, `role`, `added_by`, `created_at`, `updated_at`) VALUES
(1, 2, 'teacher', 1, '2025-10-24 22:45:44', '2025-10-25 00:48:33'),
(1, 357, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 358, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 359, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 360, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 361, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 362, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 363, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 364, 'student', 2, '2025-10-25 09:59:56', '2025-10-25 09:59:56'),
(1, 365, 'student', 2, '2025-10-25 09:59:56', '2025-10-25 09:59:56'),
(1, 366, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 367, 'student', 2, '2025-10-25 09:59:56', '2025-10-25 09:59:56'),
(1, 368, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 369, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 370, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 371, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 372, 'student', 2, '2025-10-25 09:59:56', '2025-10-25 09:59:56'),
(1, 373, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 374, 'student', 2, '2025-10-25 09:59:56', '2025-10-25 09:59:56'),
(1, 375, 'student', 2, '2025-10-25 09:59:56', '2025-10-25 09:59:56'),
(1, 376, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 377, 'student', 2, '2025-10-25 09:59:56', '2025-10-25 09:59:56'),
(1, 378, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 379, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 380, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 381, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 382, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 383, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 384, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 385, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 386, 'student', 2, '2025-10-25 09:59:56', '2025-10-25 09:59:56'),
(1, 387, 'student', 2, '2025-10-25 09:59:56', '2025-10-25 09:59:56'),
(1, 388, 'student', 2, '2025-10-25 11:02:49', '2025-10-25 11:02:49');

-- --------------------------------------------------------

--
-- Table structure for table `comment`
--

CREATE TABLE `comment` (
  `comment_id` int NOT NULL,
  `content_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `comment_text` text NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `task_id` int NOT NULL,
  `reply_comment` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `content`
--

CREATE TABLE `content` (
  `content_id` int NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `response_id` int DEFAULT NULL,
  `type_create` varchar(500) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `content_category`
--

CREATE TABLE `content_category` (
  `content_id` int NOT NULL,
  `id_category` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `content_list`
--

CREATE TABLE `content_list` (
  `id_content_list` int NOT NULL,
  `name_content_list` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deliver`
--

CREATE TABLE `deliver` (
  `id_deliver` int NOT NULL,
  `task_id` int NOT NULL,
  `type` enum('class','group','user') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` int NOT NULL COMMENT 'Phân công đối tượng nào thì sẽ truyền vào value đối tượng đó'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `detail_evaluate`
--

CREATE TABLE `detail_evaluate` (
  `id_detail_eval` int NOT NULL,
  `tc_id` int NOT NULL,
  `diem` int NOT NULL,
  `id_evaluate` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `evaluate`
--

CREATE TABLE `evaluate` (
  `id_evaluate` int NOT NULL,
  `task_id` int NOT NULL,
  `evaluate_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_score` decimal(6,2) NOT NULL DEFAULT '10.00',
  `id_assignment` int NOT NULL,
  `response_id` int DEFAULT NULL,
  `number` int DEFAULT NULL,
  `object` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `evaluate_type`
--

CREATE TABLE `evaluate_type` (
  `id_eval_type` int NOT NULL,
  `name_eval_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `generate`
--

CREATE TABLE `generate` (
  `id_gen` int NOT NULL,
  `name_gen` varchar(255) DEFAULT NULL,
  `prompt_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `group`
--

CREATE TABLE `group` (
  `group_id` int NOT NULL,
  `class_id` int NOT NULL,
  `group_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `group`
--

INSERT INTO `group` (`group_id`, `class_id`, `group_name`, `created_at`, `updated_at`) VALUES
(1, 1, 'Nhóm 1', '2025-10-25 02:22:46', '2025-10-25 02:22:46'),
(2, 1, 'Nhóm 2', '2025-10-25 02:23:29', '2025-10-25 02:23:29');

-- --------------------------------------------------------

--
-- Table structure for table `group_member`
--

CREATE TABLE `group_member` (
  `group_id` int NOT NULL,
  `user_id` int NOT NULL,
  `added_by` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `main_phase`
--

CREATE TABLE `main_phase` (
  `m_phase_id` int NOT NULL,
  `m_phase_name` varchar(100) NOT NULL,
  `phase_code` varchar(100) NOT NULL,
  `AI` tinyint(1) NOT NULL,
  `Comment` tinyint(1) NOT NULL,
  `Edit` tinyint(1) NOT NULL,
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `main_phase`
--

INSERT INTO `main_phase` (`m_phase_id`, `m_phase_name`, `phase_code`, `AI`, `Comment`, `Edit`, `description`) VALUES
(1, 'Thực hiện nhiệm vụ (sử dụng công cụ AI và tự phản biện)', 'PHASE_START', 1, 1, 0, 'Người học sử dụng công cụ AI để tạo ra nội dung phân tích văn học. Sau đó người học sẽ tự phản biện các nội dung được tạo ra'),
(2, 'Chữa bài', 'PHASE_CHUABAI', 0, 1, 0, 'Người học và giáo viên chữa bài bằng cách bình luận vào các nội dung cần sửa'),
(3, 'Làm bài tập về nhà', 'PHASE_BTVN', 1, 1, 1, 'Người học thực hiện yêu cầu được giao'),
(4, 'Đánh giá đồng đẳng', 'PHASE_DANHGIANGUOIHOC', 0, 1, 0, 'Người được phân công đánh giá sẽ đánh giá điểm cho từng bài được chỉ định'),
(5, 'Chấm điểm', 'PHASE_CHAMDIEM', 0, 1, 0, 'Giáo viên chấm điểm kết luận cho các bài');

-- --------------------------------------------------------

--
-- Table structure for table `nhom_tc`
--

CREATE TABLE `nhom_tc` (
  `nhom_tc_id` int NOT NULL,
  `ten_nhom_tc` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `object_review`
--

CREATE TABLE `object_review` (
  `id_obj_review` int NOT NULL,
  `target_type` enum('user','group') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_id` int NOT NULL,
  `id_evaluate` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `phase`
--

CREATE TABLE `phase` (
  `phase_id` int NOT NULL,
  `phase_number` int DEFAULT NULL,
  `phase_name` varchar(100) DEFAULT NULL,
  `m_phase_id` int DEFAULT NULL,
  `topic_id` int DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `flag_active` tinyint DEFAULT NULL,
  `added_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `response`
--

CREATE TABLE `response` (
  `id_response` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `parent_response` int DEFAULT NULL,
  `task_id` int DEFAULT NULL,
  `topic_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `result_evaluate`
--

CREATE TABLE `result_evaluate` (
  `id_result_eval` int NOT NULL,
  `id_evaluate` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `score`
--

CREATE TABLE `score` (
  `score_id` int NOT NULL,
  `id_evaluate` int NOT NULL,
  `grade` decimal(4,2) NOT NULL,
  `id_assignment` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `score_detail`
--

CREATE TABLE `score_detail` (
  `id_score_detail` int NOT NULL,
  `score_id` int NOT NULL,
  `tc_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `setting`
--

CREATE TABLE `setting` (
  `id_setting` int NOT NULL,
  `task_id` int NOT NULL,
  `number` int DEFAULT NULL,
  `evaluate_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `open_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `setting_detail`
--

CREATE TABLE `setting_detail` (
  `id_setting_detail` int NOT NULL,
  `id_setting` int NOT NULL,
  `id_obj_review` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task`
--

CREATE TABLE `task` (
  `task_id` int NOT NULL,
  `content_id` int DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text,
  `created_at` datetime DEFAULT NULL,
  `duong_dan_file` varchar(255) NOT NULL,
  `name_assignment` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_result`
--

CREATE TABLE `task_result` (
  `task_result_id` int NOT NULL,
  `typer_assignment` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `id_phan_cong` int NOT NULL,
  `value` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tieu_chi`
--

CREATE TABLE `tieu_chi` (
  `tc_id` int NOT NULL,
  `name_tc` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nhom_tc_id` int DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `min_score` int DEFAULT NULL,
  `max_score` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `topic`
--

CREATE TABLE `topic` (
  `topic_id` int NOT NULL,
  `topic_name` varchar(255) NOT NULL,
  `class_id` int NOT NULL,
  `added_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `topic`
--

INSERT INTO `topic` (`topic_id`, `topic_name`, `class_id`, `added_by`, `created_at`, `updated_at`) VALUES
(8, 'Tuần 1: Thơ lục bát', 1, 2, '2025-10-25 10:34:01', '2025-10-25 10:34:01');

-- --------------------------------------------------------

--
-- Table structure for table `total_score`
--

CREATE TABLE `total_score` (
  `id_total` int NOT NULL,
  `response_id` int NOT NULL,
  `teacher_grade` int DEFAULT NULL,
  `penalty` int DEFAULT NULL,
  `final_score` int DEFAULT NULL,
  `id_evaluate` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `role` enum('student','teacher','admin') DEFAULT 'student',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `fullname`, `username`, `email`, `password_hash`, `role`, `created_at`) VALUES
(1, 'Quản trị viên', 'admin', 'admin@gmail.com', '1', 'admin', '2025-10-22 13:35:57'),
(2, 'Nguyễn Minh Thương', 'gv1', 'nguyenminhthuong@gmail.com', '1', 'teacher', '2025-10-22 13:36:41'),
(357, 'Lương Bảo An', '0133378801', 'luongbaoan@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(358, 'Bùi Tâm Anh', '0131638629', 'buitamanh@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(359, 'Đoàn Châu Anh', '0149599874', 'doanchauanh@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(360, 'Hán Đỗ Kỳ Anh', '0145046988', 'handokyanh@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(361, 'Hoàng Anh', '0130487794', 'hoanganh@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(362, 'Lê Phan Quỳnh Anh', '0132665589', 'lephanquynhanh@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(363, 'Ngô Mai Anh', '0131044918', 'ngomaianh@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(364, 'Nguyễn Tú Anh', '0131749183', 'nguyentuanh@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(365, 'Trần Lê Tú Anh', '0148772396', 'tranletuanh@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(366, 'Lê Gia Hân', '0150352610', 'legiahan@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(367, 'Phạm Thị Diệu Hoa', '0145692640', 'phamthidieuhoa@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(368, 'Lê Đức Khiêm', '0150433750', 'leduckhiem@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(369, 'Đinh Hoàng Yến Linh', '0118430801', 'dinhhoangyenlinh@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(370, 'Hoàng Ngọc Linh', '0142720218', 'hoangngoclinh@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(371, 'Chu Tuệ Mẫn', '0149106246', 'chutueman@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(372, 'Trần Ngọc Nguyễn Minh', '0131730433', 'tranngocnguyenminh@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(373, 'Nguyễn Bảo Ngân', '0117814287', 'nguyenbaongan@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(374, 'Nguyễn Thị Minh Ngọc', '0131730336', 'nguyenthiminhngoc@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(375, 'Vũ Công Nhã', '0133129428', 'vucongnha@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(376, 'Nguyễn Hồng Nhung', '0142668466', 'nguyenhongnhung@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(377, 'Tạ Duy Quân', '0150349882', 'taduyquan@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(378, 'Đỗ Quốc Thành', '0140783057', 'doquocthanh@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(379, 'Nguyễn Minh Thư', '0118104560', 'nguyenminhthu02@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(380, 'Nguyễn Minh Thư', '0118017017', 'nguyenminhthu10@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(381, 'Nguyễn Ngọc Huyền Thư', '0118180219', 'nguyenngochuyenthu@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(382, 'Nguyễn Hoàng Khánh Thy', '0117861700', 'nguyenhoangkhanhthy@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(383, 'Dương Minh Trang', '0139294683', 'duongminhtrang@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(384, 'Đoàn Minh Trang', '0118422859', 'doanminhtrang@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(385, 'Nguyễn Phương Uyên', '0118133173', 'nguyenphuonguyen@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(386, 'Phạm Thanh Vân', '0117816346', 'phamthanhvan@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(387, 'Trương Anh Tuấn', '0155092125', 'truonganhtuan@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(388, 'Học sinh 1', 'hs1', '1@gmail.com', '1', 'student', '2025-10-25 04:02:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assignment`
--
ALTER TABLE `assignment`
  ADD PRIMARY KEY (`id_assignment`),
  ADD KEY `idx_asg_sd` (`id_setting_detail`),
  ADD KEY `idx_asg_user` (`user_id`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`id_category`);

--
-- Indexes for table `class`
--
ALTER TABLE `class`
  ADD PRIMARY KEY (`class_id`),
  ADD UNIQUE KEY `uq_class_name` (`class_name`);

--
-- Indexes for table `class_member`
--
ALTER TABLE `class_member`
  ADD PRIMARY KEY (`class_id`,`user_id`),
  ADD KEY `idx_cm_user` (`user_id`),
  ADD KEY `fk_add_by` (`added_by`);

--
-- Indexes for table `comment`
--
ALTER TABLE `comment`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `fk_comment_content` (`content_id`),
  ADD KEY `fk_comment_user` (`user_id`),
  ADD KEY `fk_comment_task` (`task_id`);

--
-- Indexes for table `content`
--
ALTER TABLE `content`
  ADD PRIMARY KEY (`content_id`),
  ADD KEY `fk_content_user` (`created_by`),
  ADD KEY `fk_rp_response` (`response_id`);

--
-- Indexes for table `content_category`
--
ALTER TABLE `content_category`
  ADD PRIMARY KEY (`content_id`,`id_category`),
  ADD KEY `fk_cc_category` (`id_category`);

--
-- Indexes for table `content_list`
--
ALTER TABLE `content_list`
  ADD PRIMARY KEY (`id_content_list`);

--
-- Indexes for table `deliver`
--
ALTER TABLE `deliver`
  ADD PRIMARY KEY (`id_deliver`),
  ADD KEY `idx_deliver_task` (`task_id`),
  ADD KEY `idx_deliver_type_value` (`type`,`value`);

--
-- Indexes for table `detail_evaluate`
--
ALTER TABLE `detail_evaluate`
  ADD PRIMARY KEY (`id_detail_eval`),
  ADD KEY `idx_de_tc` (`tc_id`),
  ADD KEY `idx_de_eval` (`id_evaluate`);

--
-- Indexes for table `evaluate`
--
ALTER TABLE `evaluate`
  ADD PRIMARY KEY (`id_evaluate`),
  ADD KEY `idx_eval_task` (`task_id`),
  ADD KEY `idx_eval_asg` (`id_assignment`),
  ADD KEY `idx_eval_resp` (`response_id`),
  ADD KEY `idx_eval_user` (`user_id`);

--
-- Indexes for table `evaluate_type`
--
ALTER TABLE `evaluate_type`
  ADD PRIMARY KEY (`id_eval_type`),
  ADD UNIQUE KEY `uq_eval_type_name` (`name_eval_type`);

--
-- Indexes for table `generate`
--
ALTER TABLE `generate`
  ADD PRIMARY KEY (`id_gen`);

--
-- Indexes for table `group`
--
ALTER TABLE `group`
  ADD PRIMARY KEY (`group_id`),
  ADD UNIQUE KEY `uq_group_class_name` (`class_id`,`group_name`),
  ADD KEY `idx_group_class` (`class_id`);

--
-- Indexes for table `group_member`
--
ALTER TABLE `group_member`
  ADD PRIMARY KEY (`group_id`,`user_id`),
  ADD KEY `fk_ui_user` (`user_id`);

--
-- Indexes for table `main_phase`
--
ALTER TABLE `main_phase`
  ADD PRIMARY KEY (`m_phase_id`);

--
-- Indexes for table `nhom_tc`
--
ALTER TABLE `nhom_tc`
  ADD PRIMARY KEY (`nhom_tc_id`);

--
-- Indexes for table `object_review`
--
ALTER TABLE `object_review`
  ADD PRIMARY KEY (`id_obj_review`),
  ADD KEY `idx_or_eval` (`id_evaluate`),
  ADD KEY `idx_or_target` (`target_type`,`target_id`);

--
-- Indexes for table `phase`
--
ALTER TABLE `phase`
  ADD PRIMARY KEY (`phase_id`),
  ADD KEY `fk_phase_mainphase` (`m_phase_id`),
  ADD KEY `fk_phase_topic` (`topic_id`);

--
-- Indexes for table `response`
--
ALTER TABLE `response`
  ADD PRIMARY KEY (`id_response`),
  ADD KEY `idx_response_user` (`user_id`),
  ADD KEY `idx_response_parent` (`parent_response`),
  ADD KEY `idx_response_task` (`task_id`),
  ADD KEY `idx_response_topic` (`topic_id`);

--
-- Indexes for table `result_evaluate`
--
ALTER TABLE `result_evaluate`
  ADD PRIMARY KEY (`id_result_eval`),
  ADD KEY `idx_re_eval` (`id_evaluate`);

--
-- Indexes for table `score`
--
ALTER TABLE `score`
  ADD PRIMARY KEY (`score_id`),
  ADD KEY `idx_score_eval` (`id_evaluate`),
  ADD KEY `idx_score_asg` (`id_assignment`);

--
-- Indexes for table `score_detail`
--
ALTER TABLE `score_detail`
  ADD PRIMARY KEY (`id_score_detail`),
  ADD KEY `idx_sd_score` (`score_id`),
  ADD KEY `idx_sd_tc` (`tc_id`);

--
-- Indexes for table `setting`
--
ALTER TABLE `setting`
  ADD PRIMARY KEY (`id_setting`),
  ADD KEY `idx_setting_task` (`task_id`);

--
-- Indexes for table `setting_detail`
--
ALTER TABLE `setting_detail`
  ADD PRIMARY KEY (`id_setting_detail`),
  ADD KEY `idx_sd_setting` (`id_setting`);

--
-- Indexes for table `task`
--
ALTER TABLE `task`
  ADD PRIMARY KEY (`task_id`),
  ADD KEY `fk_task_content` (`content_id`);

--
-- Indexes for table `task_result`
--
ALTER TABLE `task_result`
  ADD PRIMARY KEY (`task_result_id`);

--
-- Indexes for table `tieu_chi`
--
ALTER TABLE `tieu_chi`
  ADD PRIMARY KEY (`tc_id`),
  ADD KEY `idx_tc_nhom` (`nhom_tc_id`);

--
-- Indexes for table `topic`
--
ALTER TABLE `topic`
  ADD PRIMARY KEY (`topic_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `total_score`
--
ALTER TABLE `total_score`
  ADD PRIMARY KEY (`id_total`),
  ADD KEY `idx_ts_resp` (`response_id`),
  ADD KEY `idx_ts_eval` (`id_evaluate`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assignment`
--
ALTER TABLE `assignment`
  MODIFY `id_assignment` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `id_category` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class`
--
ALTER TABLE `class`
  MODIFY `class_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `comment`
--
ALTER TABLE `comment`
  MODIFY `comment_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `content`
--
ALTER TABLE `content`
  MODIFY `content_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `content_list`
--
ALTER TABLE `content_list`
  MODIFY `id_content_list` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deliver`
--
ALTER TABLE `deliver`
  MODIFY `id_deliver` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `detail_evaluate`
--
ALTER TABLE `detail_evaluate`
  MODIFY `id_detail_eval` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `evaluate`
--
ALTER TABLE `evaluate`
  MODIFY `id_evaluate` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `evaluate_type`
--
ALTER TABLE `evaluate_type`
  MODIFY `id_eval_type` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `generate`
--
ALTER TABLE `generate`
  MODIFY `id_gen` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `group`
--
ALTER TABLE `group`
  MODIFY `group_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `main_phase`
--
ALTER TABLE `main_phase`
  MODIFY `m_phase_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `nhom_tc`
--
ALTER TABLE `nhom_tc`
  MODIFY `nhom_tc_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `object_review`
--
ALTER TABLE `object_review`
  MODIFY `id_obj_review` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `phase`
--
ALTER TABLE `phase`
  MODIFY `phase_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `response`
--
ALTER TABLE `response`
  MODIFY `id_response` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `result_evaluate`
--
ALTER TABLE `result_evaluate`
  MODIFY `id_result_eval` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `score`
--
ALTER TABLE `score`
  MODIFY `score_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `score_detail`
--
ALTER TABLE `score_detail`
  MODIFY `id_score_detail` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `setting`
--
ALTER TABLE `setting`
  MODIFY `id_setting` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `setting_detail`
--
ALTER TABLE `setting_detail`
  MODIFY `id_setting_detail` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `task`
--
ALTER TABLE `task`
  MODIFY `task_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `task_result`
--
ALTER TABLE `task_result`
  MODIFY `task_result_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tieu_chi`
--
ALTER TABLE `tieu_chi`
  MODIFY `tc_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `topic`
--
ALTER TABLE `topic`
  MODIFY `topic_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `total_score`
--
ALTER TABLE `total_score`
  MODIFY `id_total` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=389;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assignment`
--
ALTER TABLE `assignment`
  ADD CONSTRAINT `fk_asg_sd` FOREIGN KEY (`id_setting_detail`) REFERENCES `setting_detail` (`id_setting_detail`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_asg_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `class_member`
--
ALTER TABLE `class_member`
  ADD CONSTRAINT `fk_add_by` FOREIGN KEY (`added_by`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `fk_cm_class` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `comment`
--
ALTER TABLE `comment`
  ADD CONSTRAINT `fk_comment_content` FOREIGN KEY (`content_id`) REFERENCES `content` (`content_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_comment_task` FOREIGN KEY (`task_id`) REFERENCES `task` (`task_id`),
  ADD CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `content`
--
ALTER TABLE `content`
  ADD CONSTRAINT `fk_content_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rp_response` FOREIGN KEY (`response_id`) REFERENCES `response` (`id_response`);

--
-- Constraints for table `content_category`
--
ALTER TABLE `content_category`
  ADD CONSTRAINT `fk_cc_category` FOREIGN KEY (`id_category`) REFERENCES `category` (`id_category`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cc_content` FOREIGN KEY (`content_id`) REFERENCES `content` (`content_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `deliver`
--
ALTER TABLE `deliver`
  ADD CONSTRAINT `fk_deliver_task` FOREIGN KEY (`task_id`) REFERENCES `task` (`task_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `detail_evaluate`
--
ALTER TABLE `detail_evaluate`
  ADD CONSTRAINT `fk_de_eval` FOREIGN KEY (`id_evaluate`) REFERENCES `evaluate` (`id_evaluate`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_de_tc` FOREIGN KEY (`tc_id`) REFERENCES `tieu_chi` (`tc_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `evaluate`
--
ALTER TABLE `evaluate`
  ADD CONSTRAINT `fk_eval_asg` FOREIGN KEY (`id_assignment`) REFERENCES `assignment` (`id_assignment`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eval_resp` FOREIGN KEY (`response_id`) REFERENCES `response` (`id_response`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eval_task` FOREIGN KEY (`task_id`) REFERENCES `task` (`task_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eval_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `group`
--
ALTER TABLE `group`
  ADD CONSTRAINT `fk_group_class` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `group_member`
--
ALTER TABLE `group_member`
  ADD CONSTRAINT `fk_gm_group` FOREIGN KEY (`group_id`) REFERENCES `group` (`group_id`),
  ADD CONSTRAINT `fk_ui_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `object_review`
--
ALTER TABLE `object_review`
  ADD CONSTRAINT `fk_or_eval` FOREIGN KEY (`id_evaluate`) REFERENCES `evaluate` (`id_evaluate`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `phase`
--
ALTER TABLE `phase`
  ADD CONSTRAINT `fk_phase_mainphase` FOREIGN KEY (`m_phase_id`) REFERENCES `main_phase` (`m_phase_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_phase_topic` FOREIGN KEY (`topic_id`) REFERENCES `topic` (`topic_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `response`
--
ALTER TABLE `response`
  ADD CONSTRAINT `fk_response_parent` FOREIGN KEY (`parent_response`) REFERENCES `response` (`id_response`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_response_task` FOREIGN KEY (`task_id`) REFERENCES `task` (`task_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_response_topic` FOREIGN KEY (`topic_id`) REFERENCES `topic` (`topic_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_response_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `result_evaluate`
--
ALTER TABLE `result_evaluate`
  ADD CONSTRAINT `fk_re_eval` FOREIGN KEY (`id_evaluate`) REFERENCES `evaluate` (`id_evaluate`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `score`
--
ALTER TABLE `score`
  ADD CONSTRAINT `fk_score_assignment` FOREIGN KEY (`id_assignment`) REFERENCES `assignment` (`id_assignment`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_score_evaluate` FOREIGN KEY (`id_evaluate`) REFERENCES `evaluate` (`id_evaluate`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `score_detail`
--
ALTER TABLE `score_detail`
  ADD CONSTRAINT `fk_sdetail_score` FOREIGN KEY (`score_id`) REFERENCES `score` (`score_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sdetail_tc` FOREIGN KEY (`tc_id`) REFERENCES `tieu_chi` (`tc_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `setting`
--
ALTER TABLE `setting`
  ADD CONSTRAINT `fk_setting_task` FOREIGN KEY (`task_id`) REFERENCES `task` (`task_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `setting_detail`
--
ALTER TABLE `setting_detail`
  ADD CONSTRAINT `fk_sd_setting` FOREIGN KEY (`id_setting`) REFERENCES `setting` (`id_setting`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `task`
--
ALTER TABLE `task`
  ADD CONSTRAINT `fk_task_content` FOREIGN KEY (`content_id`) REFERENCES `content` (`content_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `tieu_chi`
--
ALTER TABLE `tieu_chi`
  ADD CONSTRAINT `fk_tc_nhom` FOREIGN KEY (`nhom_tc_id`) REFERENCES `nhom_tc` (`nhom_tc_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `topic`
--
ALTER TABLE `topic`
  ADD CONSTRAINT `topic_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `total_score`
--
ALTER TABLE `total_score`
  ADD CONSTRAINT `fk_ts_eval` FOREIGN KEY (`id_evaluate`) REFERENCES `evaluate` (`id_evaluate`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ts_resp` FOREIGN KEY (`response_id`) REFERENCES `response` (`id_response`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
