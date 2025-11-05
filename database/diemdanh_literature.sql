-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 06, 2025 at 01:06 AM
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
(1, 'Lớp 10A1', '2025-10-25 01:15:28', '2025-10-25 01:15:28'),
(2, 'Demo', '2025-10-28 19:34:34', '2025-10-28 19:34:34'),
(3, 'Lớp 12A2', '2025-11-06 00:57:45', '2025-11-06 00:57:45');

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
(1, 2, 'teacher', 1, '2025-11-06 00:38:29', '2025-11-06 00:38:29'),
(1, 357, 'student', 1, '2025-11-06 00:40:26', '2025-11-06 00:40:26'),
(1, 358, 'student', 1, '2025-11-06 00:41:20', '2025-11-06 00:41:20'),
(1, 359, 'student', 1, '2025-11-06 00:41:20', '2025-11-06 00:41:20'),
(1, 360, 'student', 1, '2025-11-06 00:41:20', '2025-11-06 00:41:20'),
(1, 361, 'student', 1, '2025-11-06 00:41:20', '2025-11-06 00:41:20'),
(1, 362, 'student', 1, '2025-11-06 00:41:20', '2025-11-06 00:41:20'),
(1, 363, 'student', 1, '2025-11-06 00:41:20', '2025-11-06 00:41:20'),
(1, 364, 'student', 1, '2025-11-06 00:41:20', '2025-11-06 00:41:20'),
(1, 365, 'student', 1, '2025-11-06 00:41:20', '2025-11-06 00:41:20'),
(1, 366, 'student', 1, '2025-11-06 00:41:20', '2025-11-06 00:41:20'),
(1, 367, 'student', 1, '2025-11-06 00:41:20', '2025-11-06 00:41:20'),
(1, 368, 'student', 1, '2025-11-06 00:41:20', '2025-11-06 00:41:20'),
(1, 369, 'student', 1, '2025-11-06 00:41:20', '2025-11-06 00:41:20'),
(1, 370, 'student', 1, '2025-11-06 00:41:20', '2025-11-06 00:41:20'),
(1, 371, 'student', 1, '2025-11-06 00:41:20', '2025-11-06 00:41:20'),
(1, 372, 'student', 1, '2025-11-06 00:41:20', '2025-11-06 00:41:20'),
(1, 373, 'student', 1, '2025-11-06 00:41:20', '2025-11-06 00:41:20'),
(1, 374, 'student', 1, '2025-11-06 00:41:20', '2025-11-06 00:41:20'),
(1, 375, 'student', 1, '2025-11-06 00:41:20', '2025-11-06 00:41:20'),
(1, 376, 'student', 1, '2025-11-06 00:41:20', '2025-11-06 00:41:20'),
(1, 377, 'student', 1, '2025-11-06 00:41:20', '2025-11-06 00:41:20'),
(1, 378, 'student', 1, '2025-11-06 00:41:20', '2025-11-06 00:41:20'),
(1, 379, 'student', 1, '2025-11-06 00:41:20', '2025-11-06 00:41:20'),
(1, 380, 'student', 1, '2025-11-06 00:41:20', '2025-11-06 00:41:20'),
(1, 381, 'student', 1, '2025-11-06 00:41:20', '2025-11-06 00:41:20'),
(1, 382, 'student', 1, '2025-11-06 00:41:20', '2025-11-06 00:41:20'),
(1, 383, 'student', 1, '2025-11-06 00:41:20', '2025-11-06 00:41:20'),
(1, 384, 'student', 1, '2025-11-06 00:41:20', '2025-11-06 00:41:20'),
(1, 385, 'student', 1, '2025-11-06 00:41:20', '2025-11-06 00:41:20'),
(1, 386, 'student', 1, '2025-11-06 00:41:20', '2025-11-06 00:41:20'),
(1, 387, 'student', 1, '2025-11-06 00:41:20', '2025-11-06 00:41:20'),
(2, 366, 'student', 389, '2025-11-06 00:49:08', '2025-11-06 00:49:08'),
(2, 388, 'student', 389, '2025-11-06 00:39:14', '2025-11-06 00:39:14'),
(2, 389, 'teacher', 1, '2025-11-06 00:39:03', '2025-11-06 00:39:03'),
(2, 390, 'student', 389, '2025-11-06 00:39:14', '2025-11-06 00:39:14'),
(3, 2, 'teacher', 2, '2025-11-06 00:57:45', '2025-11-06 00:57:45');

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
  `response_id` int DEFAULT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `order` int NOT NULL,
  `type_create` varchar(500) DEFAULT NULL,
  `added_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deliver`
--

CREATE TABLE `deliver` (
  `id_deliver` int NOT NULL,
  `task_id` int NOT NULL,
  `type` enum('class','group','user') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` int NOT NULL COMMENT 'Phân công đối tượng nào thì sẽ truyền vào value đối tượng đó',
  `response` int DEFAULT NULL,
  `status` int NOT NULL DEFAULT '0' COMMENT '0: Đã giao\r\n1: Đang thực hiện\r\n2: Đã nộp',
  `added_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng giao nhiệm vụ';

--
-- Dumping data for table `deliver`
--

INSERT INTO `deliver` (`id_deliver`, `task_id`, `type`, `value`, `response`, `status`, `added_by`, `created_at`, `updated_at`) VALUES
(1, 20, 'group', 6, NULL, 0, 389, '2025-11-06 00:48:36', '2025-11-06 00:48:36'),
(2, 20, 'user', 388, NULL, 0, 389, '2025-11-06 00:48:47', '2025-11-06 00:48:47'),
(3, 20, 'user', 390, NULL, 0, 389, '2025-11-06 00:48:47', '2025-11-06 00:48:47');

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
(6, 2, 'Nhóm 1', '2025-11-06 00:22:16', '2025-11-06 00:22:16');

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

--
-- Dumping data for table `group_member`
--

INSERT INTO `group_member` (`group_id`, `user_id`, `added_by`, `created_at`, `updated_at`) VALUES
(6, 366, 389, '2025-11-06 00:22:30', '2025-11-06 00:22:30'),
(6, 390, 389, '2025-11-06 00:22:30', '2025-11-06 00:22:30');

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
  `teacher` tinyint(1) NOT NULL,
  `student` tinyint(1) NOT NULL,
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `main_phase`
--

INSERT INTO `main_phase` (`m_phase_id`, `m_phase_name`, `phase_code`, `AI`, `Comment`, `Edit`, `teacher`, `student`, `description`) VALUES
(1, 'Thực hiện nhiệm vụ (sử dụng công cụ AI và tự phản biện)', 'PHASE_START', 1, 1, 0, 0, 1, 'Người học sử dụng công cụ AI để tạo ra nội dung phân tích văn học. Sau đó người học sẽ tự phản biện các nội dung được tạo ra'),
(2, 'Chữa bài', 'PHASE_CHUABAI', 0, 1, 0, 1, 1, 'Người học và giáo viên chữa bài bằng cách bình luận vào các nội dung cần sửa'),
(3, 'Làm bài tập về nhà (công cụ AI)', 'PHASE_BTVN', 1, 1, 1, 0, 1, 'Người học thực hiện yêu cầu được giao'),
(4, 'Đánh giá đồng đẳng', 'PHASE_DANHGIANGUOIHOC', 0, 1, 0, 0, 1, 'Người được phân công đánh giá sẽ đánh giá điểm cho từng bài được chỉ định'),
(5, 'Chấm điểm', 'PHASE_CHAMDIEM', 0, 1, 0, 1, 0, 'Giáo viên chấm điểm kết luận cho các bài');

-- --------------------------------------------------------

--
-- Table structure for table `nhom_tc`
--

CREATE TABLE `nhom_tc` (
  `nhom_tc_id` int NOT NULL,
  `class_id` int NOT NULL,
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

--
-- Dumping data for table `phase`
--

INSERT INTO `phase` (`phase_id`, `phase_number`, `phase_name`, `m_phase_id`, `topic_id`, `start_time`, `end_time`, `flag_active`, `added_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'Phản biện nội dung tìm hiểu về thơ ABC', 1, 8, '2025-10-25 12:10:00', '2025-10-25 12:15:00', 0, 2, '2025-10-25 12:10:49', '2025-10-25 12:28:01'),
(2, 2, 'Chữa nội dung phản biện', 2, 8, '2025-10-25 12:32:00', '2025-10-25 12:52:00', 0, 2, '2025-10-25 12:30:11', '2025-10-25 12:32:28'),
(3, 3, 'Làm bài tập về nhà', 3, 8, '2025-10-25 12:48:00', '2025-10-25 12:59:00', 0, 2, '2025-10-25 12:33:02', '2025-10-25 12:37:30'),
(4, 4, 'Đánh giá chéo bài tập', 4, 8, '2025-10-10 23:07:00', '2025-11-01 23:07:00', 0, 2, '2025-10-25 23:07:28', '2025-10-25 23:07:44'),
(5, 5, 'Chấm điểm', 5, 8, '2025-10-26 13:43:00', '2025-10-30 13:46:00', 0, 2, '2025-10-26 13:41:39', '2025-10-26 13:41:39'),
(6, 1, 'Phản biện nội dung sau khi đọc phân tích', 1, 9, '2025-10-28 12:24:00', '2025-10-29 19:29:00', 0, 2, '2025-10-28 19:25:04', '2025-10-28 19:25:04'),
(7, 1, 'Thực hiện tìm hiểu tác phẩm', 1, 10, '2025-10-28 20:32:00', '2025-10-29 22:34:00', 0, 2, '2025-10-28 19:31:44', '2025-10-28 19:31:44'),
(8, 1, 'TÌm hiểu và phân tích lại', 1, 12, '2025-11-04 16:29:00', '2025-11-05 16:32:00', 0, 2, '2025-11-04 16:27:40', '2025-11-04 16:27:40'),
(9, 2, 'Chữa BTVN', 2, 12, '2025-11-04 16:29:00', '2025-11-05 16:32:00', 0, 2, '2025-11-04 16:28:00', '2025-11-04 16:28:00'),
(10, 3, 'BTVN', 3, 12, '2025-11-06 00:58:00', '2025-11-06 13:00:00', 0, 2, '2025-11-05 00:58:24', '2025-11-05 00:58:24'),
(11, 1, 'Tìm hiểu nội dung bằng công cụ', 1, 11, '2025-11-06 00:21:00', '2025-11-06 01:21:00', 0, 389, '2025-11-06 00:21:27', '2025-11-06 00:21:27');

-- --------------------------------------------------------

--
-- Table structure for table `response`
--

CREATE TABLE `response` (
  `id_response` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `owner_user_id` int DEFAULT NULL,
  `parent_response` int DEFAULT NULL,
  `task_id` int DEFAULT NULL,
  `json_ai` json DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `response`
--

INSERT INTO `response` (`id_response`, `user_id`, `owner_user_id`, `parent_response`, `task_id`, `json_ai`, `created_at`, `updated_at`) VALUES
(1, 388, 388, NULL, 16, NULL, '2025-11-06 00:37:57', '2025-11-06 00:37:57'),
(2, 388, 388, 1, 17, NULL, '2025-11-06 00:37:57', '2025-11-06 00:37:57');

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
  `phase_id` int NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `description` text,
  `file_content` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `content_creator` int DEFAULT NULL,
  `review_task` int DEFAULT NULL,
  `added_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Các nhiệm vụ trong phase';

--
-- Dumping data for table `task`
--

INSERT INTO `task` (`task_id`, `phase_id`, `title`, `description`, `file_content`, `content_creator`, `review_task`, `added_by`, `created_at`, `updated_at`) VALUES
(3, 1, 'Phân tích bài thơ Sắc thu', 'Em hãy phân tích bài thơ Sắc thu', 'tasks/task_68fc833d1f7b5.txt', 2, NULL, 2, '2025-10-25 14:51:03', '2025-10-25 14:58:53'),
(5, 1, 'Phân tích bài thơ Tản mạn Tây Hồ', 'Em hãy sử dụng các công cụ để phân tích bài thơ', 'tasks/task_68fc826a4e99a.txt', 2, NULL, 2, '2025-10-25 14:55:22', '2025-10-25 14:55:22'),
(6, 2, 'Chữa bài Sắc thu', '', NULL, NULL, 3, 2, '2025-10-26 15:57:29', '2025-10-26 16:02:01'),
(7, 2, 'Chữa bài Tản mạn Tây Hồ', '', NULL, NULL, 5, 2, '2025-10-27 04:38:31', '2025-10-27 04:38:31'),
(8, 3, 'Phân tích bài thơ Vội Vàng', 'Làm rõ nội dung phân tích, miêu tả đặc trưng của khung cảnh', 'tasks/task_68fede6069829.txt', 2, NULL, 2, '2025-10-27 09:52:17', '2025-10-27 09:52:17'),
(9, 4, 'Đánh giá chéo bài tập', 'Cô sẽ lấy bài tập của một bạn rồi các bạn hãy phân tích, chấm điểm cho bài bạn đó', NULL, NULL, 5, 2, '2025-10-27 10:03:31', '2025-10-27 10:03:31'),
(11, 3, 'Phân tích bài thơ Đồng Chí', '', 'tasks/task_6900827d21ab7.txt', 2, NULL, 2, '2025-10-28 15:44:44', '2025-10-28 15:44:44'),
(12, 7, 'Phân tích tác phẩm Hồn trương ba', 'Các em phân tích đặc trưng nghệ thuật', 'tasks/task_6900b832c9074_vonhat.txt', NULL, NULL, 2, '2025-10-28 19:33:55', '2025-10-28 19:34:24'),
(14, 2, 'Chữa bài', 'Chữa bài chi tiết', NULL, NULL, 5, 2, '2025-11-03 23:21:58', '2025-11-03 23:21:58'),
(15, 2, 'Chữa bài Sắc thu', 'Cô test', NULL, NULL, 3, 2, '2025-11-04 01:03:49', '2025-11-04 01:03:49'),
(16, 8, 'Phân tích bài Tự do', 'tự phân tích lại', 'tasks/task_6909c7953225c.txt', 2, NULL, 2, '2025-11-04 16:29:57', '2025-11-04 16:29:57'),
(17, 9, 'Chữa phân tích Tự do', '', NULL, NULL, 16, 2, '2025-11-04 16:30:23', '2025-11-04 16:30:23'),
(18, 10, '(BTVN) Phân tích truyện', 'Tự phản biện tự phân tích', 'tasks/task_690a4065d6189.txt', 2, NULL, 2, '2025-11-05 01:00:54', '2025-11-05 01:05:25'),
(19, 5, 'Chấm điểm tuần 1', '', NULL, NULL, 5, 2, '2025-11-05 08:41:18', '2025-11-05 08:41:18'),
(20, 11, 'Phân tích bài thơ Khúc mùa thu', '', 'tasks/task_690b88237048e.txt', 389, NULL, 389, '2025-11-06 00:23:48', '2025-11-06 00:23:48');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Quản lý các chủ đề học tập trong lớp';

--
-- Dumping data for table `topic`
--

INSERT INTO `topic` (`topic_id`, `topic_name`, `class_id`, `added_by`, `created_at`, `updated_at`) VALUES
(8, 'Tuần 1: Thơ lục bát', 1, 2, '2025-10-25 10:34:01', '2025-10-25 10:34:01'),
(9, 'Tuần 2: Truyện hiện đại', 1, 2, '2025-10-28 19:23:57', '2025-10-28 19:23:57'),
(10, 'Tuần 3: Tìm hiểu về Kịch', 1, 2, '2025-10-28 19:29:49', '2025-10-28 19:29:49'),
(11, 'Thơ tự do', 2, 389, '2025-10-28 19:37:17', '2025-10-28 19:37:17'),
(12, 'Tuần 4: Truyện Ngắn', 1, 2, '2025-11-04 16:27:09', '2025-11-04 16:27:09');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Bảng quản lý người dùng';

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
(388, 'Học sinh 1', 'hs1', '1@gmail.com', '1', 'student', '2025-10-25 04:02:41'),
(389, 'GV Long', 'gvl', 'longtt@longit.id.vn', '1', 'teacher', '2025-10-28 12:35:52'),
(390, 'HS Long', 'hsl', 'trantuanlong@haianers.top', '1', 'student', '2025-10-28 12:35:52');

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
  ADD KEY `fk_rp_response` (`response_id`);

--
-- Indexes for table `deliver`
--
ALTER TABLE `deliver`
  ADD PRIMARY KEY (`id_deliver`),
  ADD KEY `idx_deliver_task` (`task_id`),
  ADD KEY `idx_deliver_type_value` (`type`,`value`),
  ADD KEY `fk_response` (`response`);

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
  ADD KEY `fk_response_owner` (`owner_user_id`);

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
  ADD KEY `fk_users_addedBy` (`added_by`),
  ADD KEY `fk_task_reviewTask` (`review_task`),
  ADD KEY `fk_phase_phaseId` (`phase_id`),
  ADD KEY `fk_user_contentCreator` (`content_creator`);

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
-- AUTO_INCREMENT for table `class`
--
ALTER TABLE `class`
  MODIFY `class_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
-- AUTO_INCREMENT for table `deliver`
--
ALTER TABLE `deliver`
  MODIFY `id_deliver` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `group_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
  MODIFY `phase_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `response`
--
ALTER TABLE `response`
  MODIFY `id_response` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `task_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `tieu_chi`
--
ALTER TABLE `tieu_chi`
  MODIFY `tc_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `topic`
--
ALTER TABLE `topic`
  MODIFY `topic_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `total_score`
--
ALTER TABLE `total_score`
  MODIFY `id_total` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=391;

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
  ADD CONSTRAINT `fk_rp_response` FOREIGN KEY (`response_id`) REFERENCES `response` (`id_response`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `deliver`
--
ALTER TABLE `deliver`
  ADD CONSTRAINT `fk_deliver_task` FOREIGN KEY (`task_id`) REFERENCES `task` (`task_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_response` FOREIGN KEY (`response`) REFERENCES `response` (`id_response`) ON DELETE CASCADE ON UPDATE SET NULL;

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
  ADD CONSTRAINT `fk_response_owner` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_response_parent` FOREIGN KEY (`parent_response`) REFERENCES `response` (`id_response`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_response_task` FOREIGN KEY (`task_id`) REFERENCES `task` (`task_id`) ON DELETE CASCADE ON UPDATE CASCADE,
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
  ADD CONSTRAINT `fk_phase_phaseId` FOREIGN KEY (`phase_id`) REFERENCES `phase` (`phase_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `fk_task_reviewTask` FOREIGN KEY (`review_task`) REFERENCES `task` (`task_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `fk_user_contentCreator` FOREIGN KEY (`content_creator`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `fk_users_addedBy` FOREIGN KEY (`added_by`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

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
