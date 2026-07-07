-- Literature Learning System — English seed (v2) + demo activity data
-- Regenerated: Jun 12, 2026
-- Notes:
--   * Framework data (classes, topics, phases, tasks, criteria) in English.
--   * Titles of Vietnamese literary works are kept in Vietnamese with full diacritics.
--   * User accounts and class/group memberships are preserved unchanged.
--   * Demo submissions, peer reviews, comments, and consensus records included
--     for paper screenshots and system credibility.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `diemdanh_literature`
--

DROP TABLE IF EXISTS `assignment`;
DROP TABLE IF EXISTS `class`;
DROP TABLE IF EXISTS `class_member`;
DROP TABLE IF EXISTS `comment`;
DROP TABLE IF EXISTS `content`;
DROP TABLE IF EXISTS `deliver`;
DROP TABLE IF EXISTS `deliver_evaluate`;
DROP TABLE IF EXISTS `detail_evaluate`;
DROP TABLE IF EXISTS `evaluate`;
DROP TABLE IF EXISTS `evaluate_type`;
DROP TABLE IF EXISTS `generate`;
DROP TABLE IF EXISTS `group`;
DROP TABLE IF EXISTS `group_member`;
DROP TABLE IF EXISTS `main_phase`;
DROP TABLE IF EXISTS `nhom_tc`;
DROP TABLE IF EXISTS `object_review`;
DROP TABLE IF EXISTS `peer_review_chat`;
DROP TABLE IF EXISTS `peer_review_consensus`;
DROP TABLE IF EXISTS `peer_review_individual_score`;
DROP TABLE IF EXISTS `phase`;
DROP TABLE IF EXISTS `reflect_detail`;
DROP TABLE IF EXISTS `reflect_ticket`;
DROP TABLE IF EXISTS `reflect_type`;
DROP TABLE IF EXISTS `response`;
DROP TABLE IF EXISTS `result_evaluate`;
DROP TABLE IF EXISTS `score`;
DROP TABLE IF EXISTS `score_detail`;
DROP TABLE IF EXISTS `setting`;
DROP TABLE IF EXISTS `setting_detail`;
DROP TABLE IF EXISTS `task`;
DROP TABLE IF EXISTS `task_evaluate`;
DROP TABLE IF EXISTS `tieu_chi`;
DROP TABLE IF EXISTS `topic`;
DROP TABLE IF EXISTS `total_score`;
DROP TABLE IF EXISTS `users`;

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
(2, 'Demo', '2025-10-28 19:34:34', '2025-10-28 19:34:34'),
(3, 'Class 12XH1.0', '2025-11-06 00:57:45', '2025-11-08 20:38:40'),
(4, 'Experiment', '2026-03-06 15:16:18', '2026-03-06 15:16:18');

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
(2, 2, 'teacher', 389, '2025-11-07 16:46:57', '2025-11-07 16:46:57'),
(2, 366, 'student', 389, '2025-11-06 00:49:08', '2025-11-06 00:49:08'),
(2, 388, 'student', 389, '2025-11-06 00:39:14', '2025-11-06 00:39:14'),
(2, 389, 'teacher', 1, '2025-11-06 00:39:03', '2025-11-06 00:39:03'),
(2, 390, 'student', 389, '2025-11-06 00:39:14', '2025-11-06 00:39:14'),
(2, 391, 'student', 389, '2025-11-07 16:45:36', '2025-11-07 16:45:36'),
(2, 392, 'student', 389, '2025-11-07 16:45:36', '2025-11-07 16:45:36'),
(2, 393, 'student', 389, '2025-11-07 16:45:36', '2025-11-07 16:45:36'),
(2, 394, 'student', 389, '2025-11-07 16:45:36', '2025-11-07 16:45:36'),
(2, 395, 'student', 389, '2025-11-07 16:45:36', '2025-11-07 16:45:36'),
(2, 396, 'student', 389, '2025-11-07 16:45:36', '2025-11-07 16:45:36'),
(2, 397, 'student', 389, '2025-11-07 16:45:36', '2025-11-07 16:45:36'),
(2, 398, 'student', 389, '2025-11-07 16:45:36', '2025-11-07 16:45:36'),
(2, 399, 'student', 389, '2025-11-07 16:45:36', '2025-11-07 16:45:36'),
(3, 1, 'student', 2, '2025-11-08 20:11:00', '2025-11-08 20:11:00'),
(3, 2, 'teacher', 2, '2025-11-06 00:57:45', '2025-11-06 00:57:45'),
(3, 357, 'student', 2, '2025-11-08 20:11:01', '2025-11-08 20:11:01'),
(3, 358, 'student', 2, '2025-11-08 20:11:00', '2025-11-08 20:11:00'),
(3, 359, 'student', 2, '2025-11-08 20:11:00', '2025-11-08 20:11:00'),
(3, 360, 'student', 2, '2025-11-08 20:11:00', '2025-11-08 20:11:00'),
(3, 361, 'student', 2, '2025-11-08 20:11:00', '2025-11-08 20:11:00'),
(3, 362, 'student', 2, '2025-11-08 20:11:01', '2025-11-08 20:11:01'),
(3, 363, 'student', 2, '2025-11-08 20:11:01', '2025-11-08 20:11:01'),
(3, 364, 'student', 2, '2025-11-08 20:11:01', '2025-11-08 20:11:01'),
(3, 365, 'student', 2, '2025-11-08 20:11:00', '2025-11-08 20:11:00'),
(3, 366, 'student', 2, '2025-11-08 20:11:01', '2025-11-08 20:11:01'),
(3, 367, 'student', 2, '2025-11-08 20:11:01', '2025-11-08 20:11:01'),
(3, 368, 'student', 2, '2025-11-08 20:11:01', '2025-11-08 20:11:01'),
(3, 369, 'student', 2, '2025-11-08 20:11:00', '2025-11-08 20:11:00'),
(3, 370, 'student', 2, '2025-11-08 20:11:00', '2025-11-08 20:11:00'),
(3, 371, 'student', 2, '2025-11-08 20:11:00', '2025-11-08 20:11:00'),
(3, 372, 'student', 2, '2025-11-08 20:11:00', '2025-11-08 20:11:00'),
(3, 373, 'student', 2, '2025-11-08 20:11:01', '2025-11-08 20:11:01'),
(3, 374, 'student', 2, '2025-11-08 20:11:01', '2025-11-08 20:11:01'),
(3, 375, 'student', 2, '2025-11-08 20:11:01', '2025-11-08 20:11:01'),
(3, 376, 'student', 2, '2025-11-08 20:11:01', '2025-11-08 20:11:01'),
(3, 377, 'student', 2, '2025-11-08 20:11:01', '2025-11-08 20:11:01'),
(3, 378, 'student', 2, '2025-11-08 20:11:00', '2025-11-08 20:11:00'),
(3, 379, 'student', 2, '2025-11-08 20:11:01', '2025-11-08 20:11:01'),
(3, 380, 'student', 2, '2025-11-08 20:11:01', '2025-11-08 20:11:01'),
(3, 381, 'student', 2, '2025-11-08 20:11:00', '2025-11-08 20:11:00'),
(3, 382, 'student', 2, '2025-11-08 20:11:01', '2025-11-08 20:11:01'),
(3, 383, 'student', 2, '2025-11-08 20:11:00', '2025-11-08 20:11:00'),
(3, 384, 'student', 2, '2025-11-08 20:11:00', '2025-11-08 20:11:00'),
(3, 385, 'student', 2, '2025-11-08 20:11:01', '2025-11-08 20:11:01'),
(3, 386, 'student', 2, '2025-11-08 20:11:01', '2025-11-08 20:11:01'),
(3, 387, 'student', 2, '2025-11-08 20:11:00', '2025-11-08 20:11:00'),
(3, 388, 'student', 2, '2025-11-08 20:11:00', '2025-11-08 20:11:00'),
(3, 390, 'student', 2, '2025-11-08 20:11:00', '2025-11-08 20:11:00'),
(3, 391, 'student', 2, '2025-11-08 20:11:01', '2025-11-08 20:11:01'),
(3, 392, 'student', 2, '2025-11-08 20:11:01', '2025-11-08 20:11:01'),
(3, 393, 'student', 2, '2025-11-08 20:11:01', '2025-11-08 20:11:01'),
(3, 394, 'student', 2, '2025-11-08 20:11:01', '2025-11-08 20:11:01'),
(3, 395, 'student', 2, '2025-11-08 20:11:01', '2025-11-08 20:11:01'),
(3, 396, 'student', 2, '2025-11-08 20:11:01', '2025-11-08 20:11:01'),
(3, 397, 'student', 2, '2025-11-08 20:11:01', '2025-11-08 20:11:01'),
(3, 398, 'student', 2, '2025-11-08 20:11:01', '2025-11-08 20:11:01'),
(4, 388, 'student', 389, '2026-03-08 09:24:45', '2026-03-08 09:24:45'),
(4, 389, 'teacher', 389, '2026-03-06 15:16:18', '2026-03-06 15:16:18'),
(4, 391, 'student', 389, '2026-03-08 09:24:45', '2026-03-08 09:24:45'),
(4, 392, 'student', 389, '2026-03-08 09:24:45', '2026-03-08 09:24:45'),
(4, 401, 'student', 389, '2026-03-06 15:18:59', '2026-03-06 15:18:59'),
(4, 402, 'student', 389, '2026-03-06 15:18:59', '2026-03-06 15:18:59');

-- --------------------------------------------------------

--
-- Table structure for table `comment` (data cleared)
--

CREATE TABLE `comment` (
  `comment_id` int NOT NULL,
  `reponse_general` int DEFAULT NULL,
  `content_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `comment_text` text NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `task_id` int NOT NULL,
  `reply_comment` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `content` (data cleared)
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
-- Table structure for table `deliver` (data cleared)
--

CREATE TABLE `deliver` (
  `id_deliver` int NOT NULL,
  `task_id` int NOT NULL,
  `type` enum('class','group','user') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` int NOT NULL COMMENT 'ID of the assigned target (class/group/user)',
  `response` int DEFAULT NULL,
  `status` int NOT NULL DEFAULT '0' COMMENT '0: Assigned\r\n1: In progress\r\n2: Submitted',
  `added_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Task assignment table';

-- --------------------------------------------------------

--
-- Table structure for table `deliver_evaluate` (data cleared)
--

CREATE TABLE `deliver_evaluate` (
  `id_deliver` int NOT NULL,
  `response_review` int NOT NULL COMMENT 'Response to be reviewed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `detail_evaluate` (data cleared)
--

CREATE TABLE `detail_evaluate` (
  `id_detail_eval` int NOT NULL,
  `tc_id` int NOT NULL,
  `diem` int DEFAULT NULL,
  `id_evaluate` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `evaluate` (data cleared)
--

CREATE TABLE `evaluate` (
  `id_evaluate` int NOT NULL,
  `deliver_id` int NOT NULL,
  `status` tinyint NOT NULL COMMENT '0: Not graded\r\n1: Grading\r\n2: Graded',
  `is_teacher` tinyint NOT NULL DEFAULT '0',
  `total_score` decimal(6,2) DEFAULT NULL,
  `accuracy_score` decimal(6,2) DEFAULT NULL,
  `response_id` int DEFAULT NULL,
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
(6, 2, 'Group 1', '2025-11-06 00:22:16', '2025-11-06 00:22:16'),
(7, 2, 'Group 2', '2025-11-06 02:22:20', '2025-11-06 02:22:20'),
(8, 4, 'Team Alpha', '2026-03-06 15:20:00', '2026-03-06 15:20:00'),
(9, 4, 'Team Beta', '2026-03-06 15:20:30', '2026-03-06 15:20:30');

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
(6, 390, 389, '2025-11-06 00:22:30', '2025-11-06 00:22:30'),
(7, 388, 389, '2025-11-06 02:22:43', '2025-11-06 02:22:43'),
(7, 390, 389, '2025-11-06 02:22:43', '2025-11-06 02:22:43'),
(8, 388, 389, '2026-03-06 15:21:00', '2026-03-06 15:21:00'),
(8, 391, 389, '2026-03-06 15:21:00', '2026-03-06 15:21:00'),
(9, 392, 389, '2026-03-06 15:21:30', '2026-03-06 15:21:30'),
(9, 401, 389, '2026-03-06 15:21:30', '2026-03-06 15:21:30');

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
(1, 'Task execution (AI tools and self-critique)', 'PHASE_START', 1, 1, 0, 0, 1, 'Learners use AI tools to generate literary analysis content, then critically review the generated content themselves.'),
(2, 'Reviewing and correcting work', 'PHASE_CHUABAI', 0, 1, 0, 1, 1, 'Learners and the teacher correct the work by commenting on the parts that need revision.'),
(3, 'Homework (AI tools)', 'PHASE_BTVN', 1, 1, 1, 0, 1, 'Learners complete the assigned homework tasks.'),
(4, 'Peer assessment', 'PHASE_DANHGIANGUOIHOC', 0, 1, 0, 0, 1, 'Assigned reviewers grade each designated submission.'),
(5, 'Grading', 'PHASE_CHAMDIEM', 0, 1, 0, 1, 0, 'The teacher gives the concluding grades for the submissions.'),
(6, 'Knowledge acquisition', 'PHASE_TAKENOTE', 0, 1, 0, 1, 1, 'Learners take notes on key knowledge from the teacher\'s in-class lecture, connecting it with the AI analysis done earlier.');

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

--
-- Dumping data for table `nhom_tc`
--

INSERT INTO `nhom_tc` (`nhom_tc_id`, `class_id`, `ten_nhom_tc`, `description`) VALUES
(1, 2, 'Poetry assessment criteria set', ''),
(2, 1, 'Analysis grading', ''),
(3, 2, 'Story assessment criteria set', '');

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
-- Table structure for table `peer_review_chat`
--

CREATE TABLE `peer_review_chat` (
  `id` int NOT NULL,
  `consensus_id` int NOT NULL,
  `user_id` int NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `peer_review_consensus`
--

CREATE TABLE `peer_review_consensus` (
  `id` int NOT NULL,
  `id_deliver` int NOT NULL COMMENT 'ID of the deliver (reviewer person/group)',
  `response_review` int NOT NULL COMMENT 'ID of the response being reviewed',
  `status` enum('pending','discussing','agreed','timeout') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `final_score` float DEFAULT NULL COMMENT 'Final consensus score',
  `started_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `peer_review_individual_score`
--

CREATE TABLE `peer_review_individual_score` (
  `id` int NOT NULL,
  `consensus_id` int NOT NULL,
  `user_id` int NOT NULL,
  `score` float NOT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `submitted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
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
(1, 1, 'Critique the findings on the poem', 1, NULL, '2025-10-25 12:10:00', '2025-10-25 12:15:00', 0, 2, '2025-10-25 12:10:49', '2025-10-25 12:28:01'),
(2, 2, 'Correct the critiqued content', 2, NULL, '2025-10-25 12:32:00', '2025-10-25 12:52:00', 0, 2, '2025-10-25 12:30:11', '2025-10-25 12:32:28'),
(3, 3, 'Homework', 3, NULL, '2025-10-25 12:48:00', '2025-10-25 12:59:00', 0, 2, '2025-10-25 12:33:02', '2025-10-25 12:37:30'),
(4, 4, 'Cross-assess the homework', 4, NULL, '2025-10-10 23:07:00', '2025-11-01 23:07:00', 0, 2, '2025-10-25 23:07:28', '2025-10-25 23:07:44'),
(5, 5, 'Grading', 5, NULL, '2025-10-26 13:43:00', '2025-10-30 13:46:00', 0, 2, '2025-10-26 13:41:39', '2025-10-26 13:41:39'),
(6, 1, 'Critique the content after reading the analysis', 1, NULL, '2025-10-28 12:24:00', '2025-10-29 19:29:00', 0, 2, '2025-10-28 19:25:04', '2025-10-28 19:25:04'),
(7, 1, 'Explore the literary work', 1, NULL, '2025-10-28 20:32:00', '2025-10-29 22:34:00', 0, 2, '2025-10-28 19:31:44', '2025-10-28 19:31:44'),
(8, 1, 'Re-explore and re-analyze', 1, NULL, '2025-11-04 16:29:00', '2025-11-05 16:32:00', 0, 2, '2025-11-04 16:27:40', '2025-11-04 16:27:40'),
(9, 2, 'Correct the homework', 2, NULL, '2025-11-04 16:29:00', '2025-11-05 16:32:00', 0, 2, '2025-11-04 16:28:00', '2025-11-04 16:28:00'),
(10, 3, 'Homework', 3, NULL, '2025-11-06 00:58:00', '2025-11-06 13:00:00', 0, 2, '2025-11-05 00:58:24', '2025-11-05 00:58:24'),
(11, 1, 'Explore the content using tools', 1, 11, '2025-11-06 00:21:00', '2025-11-06 06:21:00', 0, 389, '2025-11-06 00:21:27', '2025-11-06 03:00:11'),
(12, 2, 'Homework', 3, 11, '2025-11-06 02:57:00', '2025-11-06 04:00:00', 0, 389, '2025-11-06 02:57:28', '2025-11-06 02:57:28'),
(13, 3, 'Correct the step-1 analysis', 2, 11, '2025-11-06 01:15:00', '2025-11-06 10:15:00', 0, 389, '2025-11-06 03:15:44', '2025-11-06 03:15:44'),
(14, 4, 'Note-taking', 6, 11, '2025-11-06 04:17:00', '2025-11-06 09:17:00', 0, 389, '2025-11-06 04:17:21', '2025-11-06 04:17:21'),
(15, 5, 'Peer assessment', 4, 11, '2025-11-06 13:35:00', '2025-11-06 15:35:00', 0, 389, '2025-11-06 13:35:45', '2025-11-06 13:35:45'),
(16, 6, 'Grading', 5, 11, '2025-11-07 11:27:00', '2025-11-08 08:30:00', 0, 389, '2025-11-07 08:27:09', '2025-11-07 08:27:09'),
(17, 1, 'Critique the analysis of the story "Lão Hạc"', 1, 14, '2025-11-07 16:29:00', '2025-11-07 22:29:00', 0, 389, '2025-11-07 16:33:32', '2025-11-07 16:33:32'),
(18, 2, 'Correct the critique', 2, 14, '2025-11-07 16:49:00', '2025-11-07 22:49:00', 0, 2, '2025-11-07 16:49:35', '2025-11-07 16:49:35'),
(19, 3, 'Re-analyze the story', 3, 14, '2025-11-07 16:49:00', '2025-11-07 22:49:00', 0, 2, '2025-11-07 16:49:59', '2025-11-07 16:49:59'),
(20, 4, 'Grading', 4, 14, '2025-11-07 16:50:00', '2025-11-07 22:50:00', 0, 2, '2025-11-07 16:50:21', '2025-11-07 16:50:21'),
(21, 1, 'Analyze the poem', 1, 16, '2025-11-08 23:24:00', '2025-11-15 23:24:00', 0, 2, '2025-11-08 20:24:40', '2025-11-08 20:25:45'),
(22, 2, 'Analyze the poem', 3, 16, '2025-11-08 23:26:00', '2025-11-15 23:26:00', 0, 2, '2025-11-08 20:26:28', '2025-11-08 20:26:28'),
(23, 1, 'Analyzing the work', 1, 17, '2026-03-06 04:00:00', '2026-03-27 16:27:00', 0, 389, '2026-03-06 15:28:46', '2026-03-06 15:29:16'),
(24, 1, 'Analyzing the work', 1, 18, '2026-03-06 04:00:00', '2026-03-27 16:00:00', 0, 389, '2026-03-06 15:33:19', '2026-03-06 15:33:19'),
(25, 2, 'Note-taking', 6, 17, '2026-03-20 13:07:00', '2026-03-29 13:07:00', 0, 389, '2026-03-20 13:02:28', '2026-03-20 13:02:28'),
(26, 3, 'Peer evaluation', 4, 17, '2062-03-21 16:12:00', '2067-03-12 05:12:00', 0, 389, '2026-03-20 13:09:37', '2026-03-20 14:48:46'),
(27, 4, 'Grading', 5, 17, '2026-03-28 15:31:00', '2026-03-29 15:31:00', 0, 389, '2026-03-20 15:31:42', '2026-03-20 15:31:42');

-- --------------------------------------------------------

--
-- Table structure for table `reflect_detail` (data cleared)
--

CREATE TABLE `reflect_detail` (
  `id_reflect_detail` int NOT NULL,
  `content` longtext NOT NULL,
  `id_user` int NOT NULL,
  `id_reflect` int NOT NULL,
  `parent_reflect_detail` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reflect_ticket` (data cleared)
--

CREATE TABLE `reflect_ticket` (
  `id_reflect` int NOT NULL,
  `title` varchar(500) NOT NULL,
  `id_user` int NOT NULL,
  `id_reflect_type` int NOT NULL,
  `status` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reflect_type`
--

CREATE TABLE `reflect_type` (
  `id_reflect_type` int NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `reflect_type`
--

INSERT INTO `reflect_type` (`id_reflect_type`, `name`) VALUES
(1, 'General feedback');

-- --------------------------------------------------------

--
-- Table structure for table `response` (data cleared)
--

CREATE TABLE `response` (
  `id_response` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `owner_user_id` int DEFAULT NULL,
  `parent_response` int DEFAULT NULL,
  `task_id` int DEFAULT NULL,
  `json_ai` json DEFAULT NULL,
  `tracking_data` json DEFAULT NULL COMMENT 'User behavior tracking data (copy, edit, paste events)',
  `tracking_updated_at` timestamp NULL DEFAULT NULL COMMENT 'Last tracking data update timestamp',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
  `phase_id` int NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `description` text,
  `file_content` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `content_creator` int DEFAULT NULL,
  `review_task` int DEFAULT NULL,
  `added_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Tasks within a phase';

--
-- Dumping data for table `task`
--

INSERT INTO `task` (`task_id`, `phase_id`, `title`, `description`, `file_content`, `content_creator`, `review_task`, `added_by`, `created_at`, `updated_at`) VALUES
(3, 1, 'Analyze the poem "Sắc thu"', 'Analyze the poem "Sắc thu"', 'tasks/task_68fc833d1f7b5.txt', 2, NULL, 2, '2025-10-25 14:51:03', '2025-10-25 14:58:53'),
(5, 1, 'Analyze the poem "Tản mạn Tây Hồ"', 'Use the tools to analyze the poem', 'tasks/task_68fc826a4e99a.txt', 2, NULL, 2, '2025-10-25 14:55:22', '2025-10-25 14:55:22'),
(6, 2, 'Correct the "Sắc thu" essays', '', NULL, NULL, 3, 2, '2025-10-26 15:57:29', '2025-10-26 16:02:01'),
(7, 2, 'Correct the "Tản mạn Tây Hồ" essays', '', NULL, NULL, 5, 2, '2025-10-27 04:38:31', '2025-10-27 04:38:31'),
(8, 3, 'Analyze the poem "Vội vàng"', 'Clarify the analysis and describe the distinctive features of the scenery', 'tasks/task_68fede6069829.txt', 2, NULL, 2, '2025-10-27 09:52:17', '2025-10-27 09:52:17'),
(9, 4, 'Cross-assess the homework', 'The teacher will pick one student\'s work and the class will analyze and grade it', NULL, NULL, 5, 2, '2025-10-27 10:03:31', '2025-10-27 10:03:31'),
(11, 3, 'Analyze the poem "Đồng chí"', '', 'tasks/task_6900827d21ab7.txt', 2, NULL, 2, '2025-10-28 15:44:44', '2025-10-28 15:44:44'),
(12, 7, 'Analyze the play "Hồn Trương Ba, da hàng thịt"', 'Analyze the artistic characteristics', 'tasks/task_6900b832c9074_vonhat.txt', NULL, NULL, 2, '2025-10-28 19:33:55', '2025-10-28 19:34:24'),
(14, 2, 'Correct the work', 'Detailed correction', NULL, NULL, 5, 2, '2025-11-03 23:21:58', '2025-11-03 23:21:58'),
(15, 2, 'Correct the "Sắc thu" essays', 'Teacher test', NULL, NULL, 3, 2, '2025-11-04 01:03:49', '2025-11-04 01:03:49'),
(16, 8, 'Analyze the poem "Tự do"', 'Re-analyze it yourself', 'tasks/task_6909c7953225c.txt', 2, NULL, 2, '2025-11-04 16:29:57', '2025-11-04 16:29:57'),
(17, 9, 'Correct the "Tự do" analysis', '', NULL, NULL, 16, 2, '2025-11-04 16:30:23', '2025-11-04 16:30:23'),
(18, 10, '(Homework) Analyze the story', 'Self-critique and re-analyze', 'tasks/task_690a4065d6189.txt', 2, NULL, 2, '2025-11-05 01:00:54', '2025-11-05 01:05:25'),
(19, 5, 'Week 1 grading', '', NULL, NULL, 5, 2, '2025-11-05 08:41:18', '2025-11-05 08:41:18'),
(20, 11, 'Analyze the poem "Khúc mùa thu"', '', 'tasks/task_690b88237048e.txt', 389, NULL, 389, '2025-11-06 00:23:48', '2025-11-06 00:23:48'),
(21, 12, 'Analyze "Bài thơ về tiểu đội xe không kính"', '', 'tasks/task_690bafc7373e1.txt', 389, NULL, 389, '2025-11-06 03:06:03', '2025-11-06 03:12:54'),
(22, 13, 'Correct the analysis', '', NULL, NULL, 20, 389, '2025-11-06 03:16:01', '2025-11-06 03:16:01'),
(23, 14, 'Note-taking', '...', NULL, NULL, 20, 389, '2025-11-06 04:18:54', '2025-11-06 04:18:54'),
(24, 15, 'Cross-assess the homework content', '', NULL, NULL, 21, 389, '2025-11-06 13:37:47', '2025-11-06 13:37:47'),
(29, 4, 'Assessment', 'Assessment', NULL, NULL, 3, 2, '2025-11-07 08:06:38', '2025-11-07 08:06:38'),
(30, 16, 'Grade the homework', '', NULL, NULL, 21, 389, '2025-11-07 08:27:27', '2025-11-07 08:27:27'),
(31, 17, 'The story "Lão Hạc"', 'Use the tools to find the analytical errors', 'tasks/task_690dbeb849f16.txt', 389, NULL, 389, '2025-11-07 16:41:12', '2025-11-07 16:41:12'),
(32, 19, 'Analyze the story "Lão Hạc"', '', 'tasks/task_690dc10f5db35_task_690dbeb849f16.txt', NULL, NULL, 2, '2025-11-07 16:51:11', '2025-11-07 16:51:11'),
(33, 20, 'Assess the analysis of the story "Lão Hạc"', '', NULL, NULL, 32, 2, '2025-11-07 16:52:01', '2025-11-07 16:52:01'),
(34, 22, 'Analyze the poem', '', 'tasks/task_690f46c13fe30.txt', 2, NULL, 2, '2025-11-08 20:33:53', '2025-11-08 20:33:53'),
(35, 21, 'Analyze the poem "Chân quê" (Nguyễn Bính)', 'Counter-argue the AI\'s analysis of the poem "Chân quê"', 'tasks/task_69a2fb1de0778.txt', 2, NULL, 2, '2025-11-08 20:41:10', '2026-02-28 21:26:37'),
(36, 23, 'Nam Cao\'s story "Lão Hạc"', 'Analysis of the story "Lão Hạc"', 'tasks/task_69aa90bd5e6e6_Lão Hạc (Nam Cao).txt', NULL, NULL, 389, '2026-03-06 15:30:53', '2026-03-06 15:30:53'),
(37, 24, 'The poem "Bài thơ về tiểu đội xe không kính"', 'Analysis of "Bài thơ về tiểu đội xe không kính" (Phạm Tiến Duật)', 'tasks/task_69aa91de2b96c_Bài thơ về tiểu đội xe không kính (Phạm Tiến Duật).txt', NULL, NULL, 389, '2026-03-06 15:35:42', '2026-03-06 15:35:42'),
(38, 25, 'Take notes on the important parts', '', NULL, NULL, 36, 389, '2026-03-20 13:02:42', '2026-03-20 13:02:42'),
(40, 26, 'Evaluating a student\'s work', '', NULL, NULL, 36, 389, '2026-03-20 14:49:43', '2026-03-20 14:49:43'),
(41, 27, 'Cross-grade other students\' work', '', NULL, NULL, 36, 389, '2026-03-20 15:32:13', '2026-03-20 15:32:13');

-- --------------------------------------------------------

--
-- Table structure for table `task_evaluate`
--

CREATE TABLE `task_evaluate` (
  `id_task` int NOT NULL,
  `id_group_tc` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `task_evaluate`
--

INSERT INTO `task_evaluate` (`id_task`, `id_group_tc`) VALUES
(24, 1),
(33, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tieu_chi`
--

CREATE TABLE `tieu_chi` (
  `tc_id` int NOT NULL,
  `name_tc` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nhom_tc_id` int DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `min_score` float DEFAULT NULL,
  `max_score` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tieu_chi`
--

INSERT INTO `tieu_chi` (`tc_id`, `name_tc`, `nhom_tc_id`, `description`, `min_score`, `max_score`) VALUES
(1, 'Thesis & Depth of Understanding', 1, '- A clear, sharp, and insightful central thesis.\r\n- Demonstrates deep understanding of the poem\'s content, themes, ideas, and emotions.\r\n- Offers an original perspective instead of repeating familiar ideas.', 0, 2),
(2, 'Analysis of Form & Artistry', 1, '- Analyzes the effectiveness of diction and imagery.\r\n- Analyzes the effect of rhetorical devices (not merely naming them).\r\n- Analyzes the role of musical elements (rhyme, rhythm, tone).\r\n- Crucially: ties the formal elements tightly to the content and thesis.', 0, 3),
(3, 'Argumentation & Evidence', 1, '- Uses accurate, relevant, and sufficient quotations.\r\n- Analyzes the evidence in depth instead of paraphrasing or retelling.\r\n- Logical reasoning; sub-arguments support the central thesis.\r\n- Persuasive reasoning that avoids unfounded subjective speculation.', 0, 3),
(4, 'Structure & Expression', 1, '- A clear, well-balanced three-part structure.\r\n- Smooth, logical transitions between ideas and paragraphs.\r\n- Coherent, lucid writing, expressive where needed and appropriate for argumentative essays.\r\n- Accurate use of terminology.', 0, 2),
(5, 'Structure & Expression', 2, '- A clear, well-balanced three-part structure.\r\n- Smooth, logical transitions between ideas and paragraphs.\r\n- Coherent, lucid writing, expressive where needed and appropriate for argumentative essays.\r\n- Accurate use of terminology.', 2, 10),
(6, 'Argumentation & Evidence', 2, '- Uses accurate, relevant, and sufficient quotations.\r\n- Analyzes the evidence in depth instead of paraphrasing or retelling.\r\n- Logical reasoning; sub-arguments support the central thesis.\r\n- Persuasive reasoning that avoids unfounded subjective speculation.', 3, 10),
(7, 'Thesis & Depth of Understanding', 2, '- A clear, sharp, and insightful central thesis.\r\n- Demonstrates deep understanding of the poem\'s content, themes, ideas, and emotions.\r\n- Offers an original perspective instead of repeating familiar ideas.', 2, 10),
(8, 'Analysis of Form & Artistry', 2, '- Analyzes the effectiveness of diction and imagery.\r\n- Analyzes the effect of rhetorical devices (not merely naming them).\r\n- Analyzes the role of musical elements (rhyme, rhythm, tone).\r\n- Crucially: ties the formal elements tightly to the content and thesis.', 2, 10),
(9, 'Plot', 3, '', 0, 10),
(10, 'Characters', 3, '', 0, 10),
(11, 'Setting & World-building', 3, '', 0, 15),
(12, 'Writing Style & Technique', 3, '', 0, 15),
(13, 'Theme & Emotional Impact', 3, '', 0, 10);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Learning topics within a class';

--
-- Dumping data for table `topic`
--

INSERT INTO `topic` (`topic_id`, `topic_name`, `class_id`, `added_by`, `created_at`, `updated_at`) VALUES
(11, 'Free verse poetry', 2, 389, '2025-10-28 19:37:17', '2025-10-28 19:37:17'),
(14, 'Short stories', 2, 389, '2025-11-07 16:28:56', '2025-11-07 16:28:56'),
(16, 'Writing an argumentative essay analyzing a poem', 3, 2, '2025-11-08 20:22:27', '2025-11-08 20:22:27'),
(17, 'Story', 4, 389, '2026-03-06 15:18:30', '2026-03-06 15:18:30'),
(18, 'Poem', 4, 389, '2026-03-06 15:18:42', '2026-03-06 15:18:42');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='User management table';

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
(390, 'HS Long', 'hsl', 'trantuanlong@haianers.top', '1', 'student', '2025-10-28 12:35:52'),
(391, 'Học sinh 2', 'hs2', 'hs2@gmail.com', '1', 'student', '2025-11-07 09:43:37'),
(392, 'Học sinh 3', 'hs3', 'hs3@gmail.com', '1', 'student', '2025-11-07 09:44:31'),
(393, 'Học sinh 4', 'hs4', 'hs4@gmail.com', '1', 'student', '2025-11-07 09:44:31'),
(394, 'Học sinh 5', 'hs5', 'hs5@gmail.com', '1', 'student', '2025-11-07 09:44:31'),
(395, 'Học sinh 6', 'hs6', 'hs6@gmail.com', '1', 'student', '2025-11-07 09:44:31'),
(396, 'Học sinh 7', 'hs7', 'hs7@gmail.com', '1', 'student', '2025-11-07 09:44:31'),
(397, 'Học sinh 8', 'hs8', 'hs8@gmail.com', '1', 'student', '2025-11-07 09:44:31'),
(398, 'Học sinh 9', 'hs9', 'hs9@gmail.com', '1', 'student', '2025-11-07 09:44:31'),
(399, 'Học sinh 10', 'hs10', 'hs10@gmail.com', '1', 'student', '2025-11-07 09:44:31'),
(401, 'Nguyễn Thị Minh Thương', 'ntm', 'thuongntm@hnue.edu.vn', '1', 'student', '2026-03-05 08:29:57'),
(402, 'Thành Đức Hồng Hà', 'hatdh', 'Hatdh@hnue.edu.vn', '1', 'student', '2026-03-05 08:38:43');

-- ============================================================
-- DEMO DATA — submissions, assignments, feedback, peer review
-- ============================================================

--
-- Dumping data for table `response`
--

INSERT INTO `response` (`id_response`, `user_id`, `owner_user_id`, `parent_response`, `task_id`, `json_ai`, `tracking_data`, `tracking_updated_at`, `created_at`, `updated_at`) VALUES
(1, 390, 390, NULL, 20, '{\"title\":\"Khúc mùa thu\",\"keywords\":[\"autumn\",\"love\",\"nostalgia\",\"loneliness\",\"time\"],\"characteristics\":{\"Genre\":\"Free verse poetry\",\"Tone\":\"Melancholic, reflective\"}}', '{\"globalSummary\":{\"sectionsEdited\":4,\"sessionDuration\":1840,\"totalCopyEvents\":6,\"totalSaveEvents\":4,\"totalCopyPercent\":32.5,\"averageEditChangePercent\":28.0}}', '2025-11-06 02:10:00', '2025-11-06 02:15:25', '2025-11-06 02:15:25'),
(2, 388, 388, NULL, 20, '{\"title\":\"Khúc mùa thu\",\"keywords\":[\"autumn\",\"memory\",\"woman\",\"obsession\"],\"characteristics\":{\"Genre\":\"Free verse poetry\",\"Tone\":\"Contemplative, bittersweet\"}}', '{\"globalSummary\":{\"sectionsEdited\":3,\"sessionDuration\":1520,\"totalCopyEvents\":4,\"totalSaveEvents\":3,\"totalCopyPercent\":24.1,\"averageEditChangePercent\":31.2}}', '2025-11-06 02:28:00', '2025-11-06 02:30:49', '2025-11-06 02:30:49'),
(3, 390, 390, NULL, 20, '{\"title\":\"Khúc mùa thu\",\"keywords\":[\"love\",\"regret\",\"autumn\"],\"characteristics\":{\"Genre\":\"Free verse poetry\",\"Tone\":\"Introspective\"}}', NULL, NULL, '2025-11-06 03:11:55', '2025-11-06 03:11:55'),
(4, 366, 366, NULL, 20, NULL, NULL, NULL, '2025-11-06 03:52:03', '2025-11-06 03:52:03'),
(5, 390, 390, NULL, 21, '{\"title\":\"Bài thơ về tiểu đội xe không kính\",\"keywords\":[\"war\",\"sacrifice\",\"youth\",\"heroism\"],\"characteristics\":{\"Genre\":\"War poetry\",\"Tone\":\"Heroic, solemn\"}}', '{\"globalSummary\":{\"sectionsEdited\":5,\"sessionDuration\":2100,\"totalCopyEvents\":7,\"totalSaveEvents\":5,\"totalCopyPercent\":38.0,\"averageEditChangePercent\":22.5}}', '2025-11-06 13:00:00', '2025-11-06 13:05:06', '2025-11-06 13:05:06'),
(6, 388, 388, NULL, 21, '{\"title\":\"Bài thơ về tiểu đội xe không kính\",\"keywords\":[\"patriotism\",\"camaraderie\",\"battlefield\"],\"characteristics\":{\"Genre\":\"War poetry\",\"Tone\":\"Reverent\"}}', NULL, NULL, '2025-11-06 13:23:39', '2025-11-06 13:23:39'),
(7, 366, 366, NULL, 21, '{\"title\":\"Bài thơ về tiểu đội xe không kính\",\"keywords\":[\"soldiers\",\"courage\",\"memory\"],\"characteristics\":{\"Genre\":\"War poetry\",\"Tone\":\"Elegiac\"}}', NULL, NULL, '2025-11-06 17:06:58', '2025-11-06 17:06:58'),
(8, 388, 388, NULL, 36, '{\"title\":\"Lão Hạc\",\"keywords\":[\"poverty\",\"dignity\",\"fatherhood\",\"realism\"],\"characteristics\":{\"Genre\":\"Short story\",\"Tone\":\"Tragic, compassionate\"}}', '{\"globalSummary\":{\"sectionsEdited\":6,\"sessionDuration\":2990,\"totalCopyEvents\":9,\"totalSaveEvents\":6,\"totalCopyPercent\":41.2,\"averageEditChangePercent\":35.8}}', '2026-03-08 02:41:35', '2026-03-08 09:28:37', '2026-04-12 02:40:54'),
(9, 391, 391, NULL, 36, '{\"title\":\"Lão Hạc\",\"keywords\":[\"peasant\",\"moral integrity\",\"sacrifice\"],\"characteristics\":{\"Genre\":\"Short story\",\"Tone\":\"Humanistic\"}}', '{\"globalSummary\":{\"sectionsEdited\":4,\"sessionDuration\":2450,\"totalCopyEvents\":5,\"totalSaveEvents\":4,\"totalCopyPercent\":28.7,\"averageEditChangePercent\":30.1}}', '2026-03-08 09:37:23', '2026-03-08 09:37:23', '2026-03-08 09:41:35'),
(10, 392, 392, NULL, 36, '{\"title\":\"Lão Hạc\",\"keywords\":[\"rural life\",\"tragedy\",\"self-respect\"],\"characteristics\":{\"Genre\":\"Short story\",\"Tone\":\"Somber, dignified\"}}', NULL, NULL, '2026-03-20 13:04:23', '2026-03-20 13:04:23'),
(11, 401, 401, NULL, 36, '{\"title\":\"Lão Hạc\",\"keywords\":[\"humanity\",\"poverty\",\"father-son bond\"],\"characteristics\":{\"Genre\":\"Short story\",\"Tone\":\"Empathetic\"}}', NULL, NULL, '2026-03-20 13:04:23', '2026-03-20 13:04:23'),
(12, 388, 388, NULL, 37, '{\"title\":\"Bài thơ về tiểu đội xe không kính\",\"keywords\":[\"war poetry\",\"youth\",\"sacrifice\"],\"characteristics\":{\"Genre\":\"War poetry\",\"Tone\":\"Patriotic\"}}', NULL, NULL, '2026-03-06 15:40:00', '2026-03-06 15:40:00'),
(13, 391, 391, NULL, 37, '{\"title\":\"Bài thơ về tiểu đội xe không kính\",\"keywords\":[\"heroism\",\"battlefield\",\"memory\"],\"characteristics\":{\"Genre\":\"War poetry\",\"Tone\":\"Solemn\"}}', NULL, NULL, '2026-03-06 15:42:00', '2026-03-06 15:42:00'),
(14, 390, 390, NULL, 35, '{\"title\":\"Chân quê\",\"keywords\":[\"nostalgia\",\"homeland\",\"rural imagery\"],\"characteristics\":{\"Genre\":\"Lyric poetry\",\"Tone\":\"Gentle, yearning\"}}', '{\"globalSummary\":{\"sectionsEdited\":3,\"sessionDuration\":1680,\"totalCopyEvents\":5,\"totalSaveEvents\":3,\"totalCopyPercent\":29.0,\"averageEditChangePercent\":26.4}}', '2025-11-08 20:45:00', '2025-11-08 20:49:00', '2025-11-08 21:58:14'),
(15, 390, 390, 1, 22, NULL, NULL, NULL, '2025-11-06 03:53:39', '2025-11-06 03:53:39'),
(16, 388, 388, 2, 22, NULL, NULL, NULL, '2025-11-06 03:58:27', '2025-11-06 03:58:27'),
(17, 388, 388, 8, 38, NULL, NULL, NULL, '2026-03-20 13:04:45', '2026-03-20 13:04:45');

--
-- Dumping data for table `content`
--

INSERT INTO `content` (`content_id`, `response_id`, `content`, `order`, `type_create`, `added_by`, `created_at`, `updated_at`) VALUES
(1, 1, '# Khúc mùa thu', 1, 'user', 390, '2025-11-06 02:15:25', '2025-11-06 02:15:25'),
(2, 1, '**Keywords:** autumn, love, nostalgia, loneliness, impermanence', 2, 'user', 390, '2025-11-06 02:15:25', '2025-11-06 02:15:25'),
(3, 1, '## Love and obsession\nThe poem explores love not only as happiness but as enduring obsession. The speaker accepts the beloved\'s flaws yet cannot escape the memory of an idealized past ("cung Hằng", "nguyệt quế").', 3, 'user', 390, '2025-11-06 02:15:25', '2025-11-06 02:15:25'),
(4, 1, '## Imagery and symbolism\nRecurring images — "giấu đêm vào trong tóc", "quầng thu thuở ấy" — create a symbolic space where autumn becomes both scenery and inner mood.', 4, 'user', 390, '2025-11-06 02:15:25', '2025-11-06 02:15:25'),
(5, 1, '## Conclusion\n"Khúc mùa thu" affirms that even unfulfilled love leaves an indelible mark on human consciousness across time.', 5, 'user', 390, '2025-11-06 02:15:25', '2025-11-06 02:15:25'),
(6, 2, '# Khúc mùa thu — Group 2 analysis', 1, 'user', 388, '2025-11-06 02:30:49', '2025-11-06 02:30:49'),
(7, 2, '## Thematic focus\nWe emphasize the contrast between youthful idealism and mature disillusionment. The rhetorical questions at the end open space for reader reflection.', 2, 'user', 388, '2025-11-06 02:30:49', '2025-11-06 02:30:49'),
(8, 2, '## Formal analysis\nFree rhythm mirrors emotional instability; partial rhymes sustain musicality without rigid structure.', 3, 'user', 388, '2025-11-06 02:30:49', '2025-11-06 02:30:49'),
(9, 3, '# Individual reflection on "Khúc mùa thu"', 1, 'user', 390, '2025-11-06 03:11:55', '2025-11-06 03:11:55'),
(10, 3, 'I revised the thesis to highlight the woman who "giấu đêm vào trong tóc" as the poem\'s central metaphor for hidden sorrow.', 2, 'user', 390, '2025-11-06 03:11:55', '2025-11-06 03:11:55'),
(11, 5, '# Bài thơ về tiểu đội xe không kính', 1, 'user', 390, '2025-11-06 13:05:06', '2025-11-06 13:05:06'),
(12, 5, '## Historical context\nWritten during the resistance war, the poem celebrates anonymous soldiers who drive open-top trucks through danger.', 2, 'user', 390, '2025-11-06 13:05:06', '2025-11-06 13:05:06'),
(13, 5, '## Artistic features\nVivid battlefield imagery, collective voice, and elevated diction transform a military episode into national myth.', 3, 'user', 390, '2025-11-06 13:05:06', '2025-11-06 13:05:06'),
(14, 6, '# Homework — war poetry analysis', 1, 'user', 388, '2025-11-06 13:23:39', '2025-11-06 13:23:39'),
(15, 6, 'The poem\'s power lies in naming the un-named: young drivers become symbols of selfless courage.', 2, 'user', 388, '2025-11-06 13:23:39', '2025-11-06 13:23:39'),
(16, 8, '# Lão Hạc (Nam Cao)', 1, 'user', 388, '2026-04-12 02:40:54', '2026-04-12 02:40:54'),
(17, 8, '## Poverty and human dignity\nOld Man Hac embodies the tragedy of the pre-revolutionary peasantry. Selling his dog "cậu Vàng" is both material desperation and spiritual wound.', 2, 'user', 388, '2026-04-12 02:40:54', '2026-04-12 02:40:54'),
(18, 8, '## Narrative perspective\nThe first-person teacher narrator moves from misunderstanding to profound empathy — a humanistic lesson in "cố tìm mà hiểu".', 3, 'user', 388, '2026-04-12 02:40:54', '2026-04-12 02:40:54'),
(19, 8, '## Intertextuality\nCompared with "Tắt đèn" (Ngô Tất Tố): both expose peasant suffering, but Nam Cao emphasizes inner tragedy over outward rebellion.', 4, 'user', 388, '2026-04-12 02:40:54', '2026-04-12 02:40:54'),
(20, 9, '# Analysis of "Lão Hạc"', 1, 'user', 391, '2026-03-08 09:37:23', '2026-03-08 09:37:23'),
(21, 9, 'Father-son love drives every decision. The garden plot symbolizes hope retained for the absent son.', 2, 'user', 391, '2026-03-08 09:37:23', '2026-03-08 09:37:23'),
(22, 10, '# Lão Hạc — moral choice at the edge of survival', 1, 'user', 392, '2026-03-20 13:04:23', '2026-03-20 13:04:23'),
(23, 10, 'The death by poisoned bait is a final act of self-respect: he protects his son\'s inheritance and refuses to burden neighbors.', 2, 'user', 392, '2026-03-20 13:04:23', '2026-03-20 13:04:23'),
(24, 12, '# Bài thơ về tiểu đội xe không kính (Phạm Tiến Duật)', 1, 'user', 388, '2026-03-06 15:40:00', '2026-03-06 15:40:00'),
(25, 12, 'The open truck becomes a metaphor for exposed vulnerability and unshielded patriotism.', 2, 'user', 388, '2026-03-06 15:40:00', '2026-03-06 15:40:00'),
(26, 14, '# Chân quê (Nguyễn Bính)', 1, 'user', 390, '2025-11-08 20:49:00', '2025-11-08 20:49:00'),
(27, 14, '## Counter-argument to AI draft\nThe AI over-emphasized urban nostalgia; the poem\'s strength is concrete rural detail and musical folk rhythm.', 2, 'user', 390, '2025-11-08 20:49:00', '2025-11-08 20:49:00'),
(28, 14, 'Revised thesis: "Chân quê" celebrates rootedness through sensory images of village life, not abstract longing alone.', 3, 'user', 390, '2025-11-08 21:58:14', '2025-11-08 21:58:14');

--
-- Dumping data for table `deliver`
--

INSERT INTO `deliver` (`id_deliver`, `task_id`, `type`, `value`, `response`, `status`, `added_by`, `created_at`, `updated_at`) VALUES
(1, 20, 'group', 6, 1, 2, 389, '2025-11-06 00:48:36', '2025-11-06 02:15:25'),
(2, 20, 'group', 7, 2, 2, 389, '2025-11-06 02:22:58', '2025-11-06 02:30:49'),
(3, 20, 'user', 390, 3, 2, 389, '2025-11-06 00:48:47', '2025-11-06 03:11:55'),
(4, 20, 'user', 366, 4, 2, 389, '2025-11-06 00:49:00', '2025-11-06 03:52:03'),
(5, 21, 'group', 6, 5, 2, 389, '2025-11-06 03:16:36', '2025-11-06 13:05:06'),
(6, 21, 'group', 7, 6, 2, 389, '2025-11-06 03:16:36', '2025-11-06 13:23:39'),
(7, 21, 'user', 366, 7, 2, 389, '2025-11-06 03:16:56', '2025-11-06 17:06:58'),
(8, 36, 'user', 388, 8, 2, 389, '2026-03-06 15:31:00', '2026-04-12 02:40:54'),
(9, 36, 'user', 391, 9, 2, 389, '2026-03-06 15:31:30', '2026-03-08 09:37:23'),
(10, 36, 'user', 392, 10, 2, 389, '2026-03-06 15:32:00', '2026-03-20 13:04:23'),
(11, 36, 'user', 401, 11, 2, 389, '2026-03-06 15:32:30', '2026-03-20 13:04:23'),
(12, 37, 'user', 388, 12, 2, 389, '2026-03-06 15:35:42', '2026-03-06 15:40:00'),
(13, 37, 'user', 391, 13, 2, 389, '2026-03-06 15:36:00', '2026-03-06 15:42:00'),
(14, 35, 'user', 390, 14, 2, 389, '2025-11-08 20:40:00', '2025-11-08 21:58:14'),
(15, 22, 'user', 390, 15, 2, 389, '2025-11-06 03:17:27', '2025-11-06 03:53:39'),
(16, 22, 'user', 388, 16, 2, 389, '2025-11-06 03:17:48', '2025-11-06 03:58:27'),
(17, 24, 'group', 6, NULL, 1, 389, '2025-11-06 13:37:47', '2025-11-06 13:37:47'),
(18, 24, 'group', 7, NULL, 1, 389, '2025-11-06 13:37:47', '2025-11-06 13:37:47'),
(19, 24, 'user', 390, NULL, 1, 389, '2025-11-06 13:38:00', '2025-11-06 13:38:00'),
(20, 24, 'user', 366, NULL, 1, 389, '2025-11-06 13:38:00', '2025-11-06 13:38:00'),
(21, 40, 'group', 8, NULL, 1, 389, '2026-03-20 14:49:43', '2026-03-20 14:49:43'),
(22, 40, 'group', 9, NULL, 1, 389, '2026-03-20 14:49:43', '2026-03-20 14:49:43');

--
-- Dumping data for table `comment`
--

INSERT INTO `comment` (`comment_id`, `reponse_general`, `content_id`, `user_id`, `comment_text`, `created_at`, `task_id`, `reply_comment`) VALUES
(1, NULL, 3, 389, 'Strong thesis on obsession — consider adding a quotation from stanza 4 as evidence.', '2025-11-06 03:57:47', 22, 0),
(2, NULL, 7, 389, 'Good formal analysis. Link the free rhythm more explicitly to the speaker\'s emotional swings.', '2025-11-06 03:58:50', 22, 0),
(3, NULL, 10, 366, 'Clear individual revision. Well done.', '2025-11-06 16:21:17', 20, 0),
(4, NULL, 13, 2, 'Please expand the section on collective voice with a cited line from the poem.', '2025-11-08 20:49:53', 35, 0),
(5, NULL, 28, 2, 'Excellent counter-argument — this shows effective AI self-critique.', '2025-11-08 21:58:14', 35, 0),
(6, NULL, 17, 389, 'The dignity theme is well argued. Add one detail about the garden as symbol.', '2026-04-12 02:46:12', 36, 0),
(7, NULL, 17, 388, 'Added intertextual comparison with "Tắt đèn" — see section 4.', '2026-04-12 02:45:21', 36, 0),
(8, 1, NULL, 389, 'Overall comment: Group 1 submission demonstrates solid AI-assisted workflow with meaningful edits.', '2025-11-10 14:43:25', 20, 0),
(9, 8, NULL, 389, 'Comprehensive analysis of "Lão Hạc". Tracking data shows healthy edit-to-copy ratio.', '2026-04-12 02:47:00', 36, 0);

--
-- Dumping data for table `deliver_evaluate`
--

INSERT INTO `deliver_evaluate` (`id_deliver`, `response_review`) VALUES
(17, 5),
(18, 6),
(19, 5),
(20, 7),
(21, 8),
(22, 9);

--
-- Dumping data for table `evaluate`
--

INSERT INTO `evaluate` (`id_evaluate`, `deliver_id`, `status`, `is_teacher`, `total_score`, `accuracy_score`, `response_id`, `user_id`) VALUES
(1, 17, 2, 0, 7.50, 82.00, 5, 390),
(2, 18, 2, 0, 8.00, 88.50, 6, 388),
(3, 19, 2, 0, 6.50, 75.00, 5, 390),
(4, 20, 2, 0, 7.00, 79.00, 7, 366),
(5, 21, 2, 0, 8.60, 91.00, 8, 388),
(6, 22, 2, 0, 7.20, 84.00, 9, 391),
(7, 8, 2, 1, 9.00, NULL, 8, 2),
(8, 9, 2, 1, 8.50, NULL, 9, 2);

--
-- Dumping data for table `detail_evaluate`
--

INSERT INTO `detail_evaluate` (`id_detail_eval`, `tc_id`, `diem`, `id_evaluate`) VALUES
(1, 1, 2, 1),
(2, 2, 2, 1),
(3, 3, 2, 1),
(4, 4, 2, 1),
(5, 1, 2, 2),
(6, 2, 3, 2),
(7, 3, 2, 2),
(8, 4, 1, 2),
(9, 1, 1, 3),
(10, 2, 2, 3),
(11, 3, 2, 3),
(12, 4, 2, 3),
(13, 1, 2, 4),
(14, 2, 2, 4),
(15, 3, 2, 4),
(16, 4, 1, 4),
(17, 9, 9, 5),
(18, 10, 8, 5),
(19, 11, 13, 5),
(20, 12, 14, 5),
(21, 13, 9, 5),
(22, 9, 8, 6),
(23, 10, 7, 6),
(24, 11, 11, 6),
(25, 12, 12, 6),
(26, 13, 8, 6);

--
-- Dumping data for table `total_score`
--

INSERT INTO `total_score` (`id_total`, `response_id`, `teacher_grade`, `penalty`, `final_score`, `id_evaluate`) VALUES
(1, 5, 8, 0, 8, 7),
(2, 8, 9, 0, 9, 7),
(3, 9, 9, 0, 9, 8);

--
-- Dumping data for table `peer_review_consensus`
--

INSERT INTO `peer_review_consensus` (`id`, `id_deliver`, `response_review`, `status`, `final_score`, `started_at`, `resolved_at`) VALUES
(1, 21, 8, 'agreed', 8.40, '2026-03-20 14:50:00', '2026-03-20 15:10:00'),
(2, 22, 9, 'discussing', NULL, '2026-03-20 14:50:30', NULL);

--
-- Dumping data for table `peer_review_individual_score`
--

INSERT INTO `peer_review_individual_score` (`id`, `consensus_id`, `user_id`, `score`, `comment`, `submitted_at`) VALUES
(1, 1, 388, 8.50, 'Strong character analysis and clear thesis on human dignity.', '2026-03-20 14:52:00'),
(2, 1, 391, 8.30, 'Good use of textual evidence; setting section could be deeper.', '2026-03-20 14:53:00'),
(3, 2, 392, 7.80, 'Solid plot summary but needs more on narrative perspective.', '2026-03-20 14:55:00'),
(4, 2, 401, 8.10, 'Empathetic reading of Old Man Hac\'s moral choices.', '2026-03-20 14:56:00');

--
-- Dumping data for table `peer_review_chat`
--

INSERT INTO `peer_review_chat` (`id`, `consensus_id`, `user_id`, `message`, `sent_at`) VALUES
(1, 2, 392, 'I gave 7.8 on characters — can we discuss the narrator section?', '2026-03-20 15:00:00'),
(2, 2, 401, 'I think 8.1 is fair; the empathy theme is well developed.', '2026-03-20 15:02:00'),
(3, 2, 392, 'Agreed to raise characters to 8.0 if we note the first-person shift.', '2026-03-20 15:05:00');

--
-- Dumping data for table `reflect_ticket`
--

INSERT INTO `reflect_ticket` (`id_reflect`, `title`, `id_user`, `id_reflect_type`, `status`, `created_at`, `updated_at`) VALUES
(1, 'AI analysis accuracy for "Lão Hạc"', 388, 1, 1, '2026-04-10 22:21:44', '2026-04-11 10:00:00'),
(2, 'Peer review rubric clarification', 390, 1, 0, '2025-11-10 22:21:44', '2025-11-10 22:21:44');

--
-- Dumping data for table `reflect_detail`
--

INSERT INTO `reflect_detail` (`id_reflect_detail`, `content`, `id_user`, `id_reflect`, `parent_reflect_detail`, `created_at`, `updated_at`) VALUES
(1, 'The AI draft missed the symbolic weight of selling "cậu Vàng". Our group corrected this in the revision phase.', 388, 1, NULL, '2026-04-10 22:21:44', '2026-04-10 22:21:44'),
(2, 'Teacher replied: Good observation — this detail is central to Nam Cao\'s psychological realism.', 389, 1, 1, '2026-04-11 10:00:00', '2026-04-11 10:00:00'),
(3, 'Could we get clearer examples for the 0–2 scale on "Thesis & Depth of Understanding"?', 390, 2, NULL, '2025-11-10 22:21:44', '2025-11-10 22:21:44');

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
  ADD KEY `fk_comment_task` (`task_id`),
  ADD KEY `fk_comment_response` (`reponse_general`);

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
-- Indexes for table `deliver_evaluate`
--
ALTER TABLE `deliver_evaluate`
  ADD KEY `fk_deliver_deliver` (`id_deliver`),
  ADD KEY `fk_response_reivew` (`response_review`);

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
  ADD KEY `idx_eval_resp` (`response_id`),
  ADD KEY `idx_eval_user` (`user_id`),
  ADD KEY `fk_deliver_evaluate` (`deliver_id`);

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
-- Indexes for table `peer_review_chat`
--
ALTER TABLE `peer_review_chat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_chat_consensus` (`consensus_id`),
  ADD KEY `fk_chat_user` (`user_id`);

--
-- Indexes for table `peer_review_consensus`
--
ALTER TABLE `peer_review_consensus`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_consensus_deliver_response` (`id_deliver`,`response_review`),
  ADD KEY `fk_consensus_response` (`response_review`);

--
-- Indexes for table `peer_review_individual_score`
--
ALTER TABLE `peer_review_individual_score`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_score_consensus_user` (`consensus_id`,`user_id`),
  ADD KEY `fk_score_user` (`user_id`);

--
-- Indexes for table `phase`
--
ALTER TABLE `phase`
  ADD PRIMARY KEY (`phase_id`),
  ADD KEY `fk_phase_mainphase` (`m_phase_id`),
  ADD KEY `fk_phase_topic` (`topic_id`);

--
-- Indexes for table `reflect_detail`
--
ALTER TABLE `reflect_detail`
  ADD PRIMARY KEY (`id_reflect_detail`),
  ADD KEY `fk_reflect_detail_user` (`id_user`),
  ADD KEY `fk_reflect_detail_ticket` (`id_reflect`),
  ADD KEY `fk_reflect_detail_parent` (`parent_reflect_detail`);

--
-- Indexes for table `reflect_ticket`
--
ALTER TABLE `reflect_ticket`
  ADD PRIMARY KEY (`id_reflect`),
  ADD KEY `fk_reflect_ticket_user` (`id_user`),
  ADD KEY `fk_reflect_ticket_type` (`id_reflect_type`);

--
-- Indexes for table `reflect_type`
--
ALTER TABLE `reflect_type`
  ADD PRIMARY KEY (`id_reflect_type`);

--
-- Indexes for table `response`
--
ALTER TABLE `response`
  ADD PRIMARY KEY (`id_response`),
  ADD KEY `idx_response_user` (`user_id`),
  ADD KEY `idx_response_parent` (`parent_response`),
  ADD KEY `idx_response_task` (`task_id`),
  ADD KEY `fk_response_owner` (`owner_user_id`),
  ADD KEY `idx_tracking_updated` (`tracking_updated_at`);

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
-- Indexes for table `task_evaluate`
--
ALTER TABLE `task_evaluate`
  ADD UNIQUE KEY `id_task` (`id_task`),
  ADD KEY `fk_group_tc_taskEvaluate` (`id_group_tc`);

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
  MODIFY `class_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `comment`
--
ALTER TABLE `comment`
  MODIFY `comment_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `content`
--
ALTER TABLE `content`
  MODIFY `content_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `deliver`
--
ALTER TABLE `deliver`
  MODIFY `id_deliver` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `detail_evaluate`
--
ALTER TABLE `detail_evaluate`
  MODIFY `id_detail_eval` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `evaluate`
--
ALTER TABLE `evaluate`
  MODIFY `id_evaluate` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
  MODIFY `group_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `main_phase`
--
ALTER TABLE `main_phase`
  MODIFY `m_phase_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `nhom_tc`
--
ALTER TABLE `nhom_tc`
  MODIFY `nhom_tc_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `object_review`
--
ALTER TABLE `object_review`
  MODIFY `id_obj_review` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `peer_review_chat`
--
ALTER TABLE `peer_review_chat`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `peer_review_consensus`
--
ALTER TABLE `peer_review_consensus`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `peer_review_individual_score`
--
ALTER TABLE `peer_review_individual_score`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `phase`
--
ALTER TABLE `phase`
  MODIFY `phase_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `reflect_detail`
--
ALTER TABLE `reflect_detail`
  MODIFY `id_reflect_detail` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `reflect_ticket`
--
ALTER TABLE `reflect_ticket`
  MODIFY `id_reflect` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reflect_type`
--
ALTER TABLE `reflect_type`
  MODIFY `id_reflect_type` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `response`
--
ALTER TABLE `response`
  MODIFY `id_response` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

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
  MODIFY `task_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `tieu_chi`
--
ALTER TABLE `tieu_chi`
  MODIFY `tc_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `topic`
--
ALTER TABLE `topic`
  MODIFY `topic_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `total_score`
--
ALTER TABLE `total_score`
  MODIFY `id_total` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=403;

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
  ADD CONSTRAINT `fk_comment_response` FOREIGN KEY (`reponse_general`) REFERENCES `response` (`id_response`) ON DELETE CASCADE,
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
  ADD CONSTRAINT `fk_response` FOREIGN KEY (`response`) REFERENCES `response` (`id_response`) ON DELETE CASCADE;

--
-- Constraints for table `deliver_evaluate`
--
ALTER TABLE `deliver_evaluate`
  ADD CONSTRAINT `fk_deliver_deliver` FOREIGN KEY (`id_deliver`) REFERENCES `deliver` (`id_deliver`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_response_reivew` FOREIGN KEY (`response_review`) REFERENCES `response` (`id_response`) ON DELETE CASCADE ON UPDATE CASCADE;

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
  ADD CONSTRAINT `fk_deliver_evaluate` FOREIGN KEY (`deliver_id`) REFERENCES `deliver` (`id_deliver`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_eval_resp` FOREIGN KEY (`response_id`) REFERENCES `response` (`id_response`) ON DELETE SET NULL ON UPDATE CASCADE,
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
-- Constraints for table `peer_review_chat`
--
ALTER TABLE `peer_review_chat`
  ADD CONSTRAINT `fk_chat_consensus` FOREIGN KEY (`consensus_id`) REFERENCES `peer_review_consensus` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_chat_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `peer_review_consensus`
--
ALTER TABLE `peer_review_consensus`
  ADD CONSTRAINT `fk_consensus_deliver` FOREIGN KEY (`id_deliver`) REFERENCES `deliver` (`id_deliver`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_consensus_response` FOREIGN KEY (`response_review`) REFERENCES `response` (`id_response`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `peer_review_individual_score`
--
ALTER TABLE `peer_review_individual_score`
  ADD CONSTRAINT `fk_score_consensus` FOREIGN KEY (`consensus_id`) REFERENCES `peer_review_consensus` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_score_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `phase`
--
ALTER TABLE `phase`
  ADD CONSTRAINT `fk_phase_mainphase` FOREIGN KEY (`m_phase_id`) REFERENCES `main_phase` (`m_phase_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_phase_topic` FOREIGN KEY (`topic_id`) REFERENCES `topic` (`topic_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `reflect_detail`
--
ALTER TABLE `reflect_detail`
  ADD CONSTRAINT `fk_reflect_detail_parent` FOREIGN KEY (`parent_reflect_detail`) REFERENCES `reflect_detail` (`id_reflect_detail`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reflect_detail_ticket` FOREIGN KEY (`id_reflect`) REFERENCES `reflect_ticket` (`id_reflect`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reflect_detail_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reflect_ticket`
--
ALTER TABLE `reflect_ticket`
  ADD CONSTRAINT `fk_reflect_ticket_type` FOREIGN KEY (`id_reflect_type`) REFERENCES `reflect_type` (`id_reflect_type`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reflect_ticket_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
-- Constraints for table `task_evaluate`
--
ALTER TABLE `task_evaluate`
  ADD CONSTRAINT `fk_group_tc_taskEvaluate` FOREIGN KEY (`id_group_tc`) REFERENCES `nhom_tc` (`nhom_tc_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_task_taskEvaluate` FOREIGN KEY (`id_task`) REFERENCES `task` (`task_id`) ON DELETE CASCADE;

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

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
