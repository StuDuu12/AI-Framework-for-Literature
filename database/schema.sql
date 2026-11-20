-- Cấu trúc DB LiteratureVer2 (KHÔNG có dữ liệu người dùng).
-- Sinh từ DB đã chạy đủ migration, ngày 2026-11-20.
-- Cài mới: tạo DB rỗng rồi import file này, sau đó chạy các file seed_*.sql nếu cần dữ liệu mẫu.


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `analysis_draft`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `analysis_draft` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `task_id` int NOT NULL,
  `deliver_id` int NOT NULL,
  `draft_data` json NOT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_analysis_draft` (`user_id`,`task_id`,`deliver_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `analysis_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `analysis_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `task_id` int NOT NULL,
  `deliver_id` int NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `analysis_data` json NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_confirmed` tinyint(1) NOT NULL DEFAULT '0',
  `confirmed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_analysis_history_lookup` (`user_id`,`task_id`,`deliver_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `api_rate_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_rate_limits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `endpoint` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `request_count` int DEFAULT '1',
  `window_start` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rl_lookup` (`ip_address`,`endpoint`,`window_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_settings` (
  `setting_key` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `assignment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assignment` (
  `id_assignment` int NOT NULL AUTO_INCREMENT,
  `id_setting_detail` int NOT NULL,
  `user_id` int NOT NULL,
  PRIMARY KEY (`id_assignment`),
  KEY `idx_asg_sd` (`id_setting_detail`),
  KEY `idx_asg_user` (`user_id`),
  CONSTRAINT `fk_asg_sd` FOREIGN KEY (`id_setting_detail`) REFERENCES `setting_detail` (`id_setting_detail`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_asg_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `badge`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `badge` (
  `badge_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'bi-award',
  `color` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'primary',
  `criteria_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g., tasks_completed, evaluations_done, streak_days',
  `criteria_value` int NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`badge_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bookmark`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bookmark` (
  `bookmark_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `task_id` int NOT NULL,
  `note` text COLLATE utf8mb4_unicode_ci COMMENT 'Personal note about this bookmark',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`bookmark_id`),
  UNIQUE KEY `unique_bookmark` (`user_id`,`task_id`),
  KEY `idx_bm_user` (`user_id`),
  KEY `idx_bm_task` (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `class`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `class` (
  `class_id` int NOT NULL AUTO_INCREMENT,
  `class_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`class_id`),
  UNIQUE KEY `uq_class_name` (`class_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `class_invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `class_invitations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class_id` int NOT NULL,
  `code` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `max_uses` int DEFAULT NULL,
  `use_count` int DEFAULT '0',
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inv_code` (`code`),
  KEY `idx_inv_class` (`class_id`),
  KEY `fk_inv_creator` (`created_by`),
  CONSTRAINT `fk_inv_class` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inv_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='M├ú mß╗Øi lß╗øp hß╗ìc';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `class_member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `class_member` (
  `class_id` int NOT NULL,
  `user_id` int NOT NULL,
  `role` enum('admin','teacher','student') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `added_by` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`class_id`,`user_id`),
  KEY `idx_cm_user` (`user_id`),
  KEY `fk_add_by` (`added_by`),
  CONSTRAINT `fk_add_by` FOREIGN KEY (`added_by`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_cm_class` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `comment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comment` (
  `comment_id` int NOT NULL AUTO_INCREMENT,
  `reponse_general` int DEFAULT NULL,
  `content_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `comment_text` text NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `task_id` int NOT NULL,
  `reply_comment` int NOT NULL,
  PRIMARY KEY (`comment_id`),
  KEY `fk_comment_content` (`content_id`),
  KEY `fk_comment_user` (`user_id`),
  KEY `fk_comment_task` (`task_id`),
  KEY `fk_comment_response` (`reponse_general`),
  CONSTRAINT `fk_comment_content` FOREIGN KEY (`content_id`) REFERENCES `content` (`content_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_comment_response` FOREIGN KEY (`reponse_general`) REFERENCES `response` (`id_response`) ON DELETE CASCADE,
  CONSTRAINT `fk_comment_task` FOREIGN KEY (`task_id`) REFERENCES `task` (`task_id`),
  CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `content` (
  `content_id` int NOT NULL AUTO_INCREMENT,
  `response_id` int DEFAULT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `order` int NOT NULL,
  `type_create` varchar(500) DEFAULT NULL,
  `added_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`content_id`),
  KEY `fk_rp_response` (`response_id`),
  CONSTRAINT `fk_rp_response` FOREIGN KEY (`response_id`) REFERENCES `response` (`id_response`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `deliver`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `deliver` (
  `id_deliver` int NOT NULL AUTO_INCREMENT,
  `task_id` int NOT NULL,
  `type` enum('class','group','user') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` int NOT NULL COMMENT 'ID of the assigned target (class/group/user)',
  `response` int DEFAULT NULL,
  `status` int NOT NULL DEFAULT '0' COMMENT '0: Assigned\r\n1: In progress\r\n2: Submitted',
  `deadline` datetime DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `added_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_deliver`),
  KEY `idx_deliver_task` (`task_id`),
  KEY `idx_deliver_type_value` (`type`,`value`),
  KEY `fk_response` (`response`),
  CONSTRAINT `fk_deliver_task` FOREIGN KEY (`task_id`) REFERENCES `task` (`task_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_response` FOREIGN KEY (`response`) REFERENCES `response` (`id_response`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Task assignment table';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `deliver_evaluate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `deliver_evaluate` (
  `id_deliver` int NOT NULL,
  `response_review` int NOT NULL COMMENT 'Response to be reviewed',
  KEY `fk_deliver_deliver` (`id_deliver`),
  KEY `fk_response_reivew` (`response_review`),
  CONSTRAINT `fk_deliver_deliver` FOREIGN KEY (`id_deliver`) REFERENCES `deliver` (`id_deliver`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_response_reivew` FOREIGN KEY (`response_review`) REFERENCES `response` (`id_response`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `detail_evaluate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detail_evaluate` (
  `id_detail_eval` int NOT NULL AUTO_INCREMENT,
  `tc_id` int NOT NULL,
  `diem` int DEFAULT NULL,
  `id_evaluate` int NOT NULL,
  PRIMARY KEY (`id_detail_eval`),
  KEY `idx_de_tc` (`tc_id`),
  KEY `idx_de_eval` (`id_evaluate`),
  CONSTRAINT `fk_de_eval` FOREIGN KEY (`id_evaluate`) REFERENCES `evaluate` (`id_evaluate`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_de_tc` FOREIGN KEY (`tc_id`) REFERENCES `tieu_chi` (`tc_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `evaluate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evaluate` (
  `id_evaluate` int NOT NULL AUTO_INCREMENT,
  `deliver_id` int NOT NULL,
  `status` tinyint NOT NULL COMMENT '0: Not graded\r\n1: Grading\r\n2: Graded',
  `is_teacher` tinyint NOT NULL DEFAULT '0',
  `total_score` decimal(6,2) DEFAULT NULL,
  `accuracy_score` decimal(6,2) DEFAULT NULL,
  `response_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  PRIMARY KEY (`id_evaluate`),
  KEY `idx_eval_resp` (`response_id`),
  KEY `idx_eval_user` (`user_id`),
  KEY `fk_deliver_evaluate` (`deliver_id`),
  CONSTRAINT `fk_deliver_evaluate` FOREIGN KEY (`deliver_id`) REFERENCES `deliver` (`id_deliver`) ON DELETE CASCADE,
  CONSTRAINT `fk_eval_resp` FOREIGN KEY (`response_id`) REFERENCES `response` (`id_response`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_eval_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `evaluate_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evaluate_type` (
  `id_eval_type` int NOT NULL AUTO_INCREMENT,
  `name_eval_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_eval_type`),
  UNIQUE KEY `uq_eval_type_name` (`name_eval_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `generate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `generate` (
  `id_gen` int NOT NULL AUTO_INCREMENT,
  `name_gen` varchar(255) DEFAULT NULL,
  `prompt_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_gen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `group` (
  `group_id` int NOT NULL AUTO_INCREMENT,
  `class_id` int NOT NULL,
  `group_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`group_id`),
  UNIQUE KEY `uq_group_class_name` (`class_id`,`group_name`),
  KEY `idx_group_class` (`class_id`),
  CONSTRAINT `fk_group_class` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group_member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `group_member` (
  `group_id` int NOT NULL,
  `user_id` int NOT NULL,
  `added_by` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`group_id`,`user_id`),
  KEY `fk_ui_user` (`user_id`),
  CONSTRAINT `fk_gm_group` FOREIGN KEY (`group_id`) REFERENCES `group` (`group_id`),
  CONSTRAINT `fk_ui_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `literary_author`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `literary_author` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_original` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `region` enum('vietnam','international') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'vietnam',
  `country` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birth_year` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `death_year` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `era` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `biography` text COLLATE utf8mb4_unicode_ci,
  `portrait_image` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wikipedia_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keywords` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_la_slug` (`slug`),
  KEY `idx_la_region` (`region`),
  FULLTEXT KEY `ft_la_search` (`name`,`biography`,`keywords`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `literary_work`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `literary_work` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Display title',
  `title_original` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Original language title if different',
  `author` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `author_id` int DEFAULT NULL,
  `author_original` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `region` enum('vietnam','international') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'vietnam',
  `country` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'e.g. Vietnam, France, USA',
  `genre` enum('poem','story','novel','drama','essay','epic') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'story',
  `genre_label` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `era` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `language` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT 'Vietnamese',
  `excerpt` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `full_content` longtext COLLATE utf8mb4_unicode_ci,
  `content_html` tinyint(1) NOT NULL DEFAULT '0',
  `keywords` text COLLATE utf8mb4_unicode_ci COMMENT 'Space-separated search keywords',
  `cover_image` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Poster/cover URL or site-relative path',
  `wikipedia_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `featured` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lw_region` (`region`),
  KEY `idx_lw_genre` (`genre`),
  KEY `idx_lw_featured` (`featured`),
  KEY `idx_lw_author` (`author_id`),
  FULLTEXT KEY `ft_lw_search` (`title`,`author`,`keywords`,`description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `main_phase`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `main_phase` (
  `m_phase_id` int NOT NULL AUTO_INCREMENT,
  `m_phase_name` varchar(100) NOT NULL,
  `phase_code` varchar(100) NOT NULL,
  `AI` tinyint(1) NOT NULL,
  `Comment` tinyint(1) NOT NULL,
  `Edit` tinyint(1) NOT NULL,
  `teacher` tinyint(1) NOT NULL,
  `student` tinyint(1) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`m_phase_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nhom_tc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nhom_tc` (
  `nhom_tc_id` int NOT NULL AUTO_INCREMENT,
  `class_id` int NOT NULL,
  `ten_nhom_tc` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `template_id` int DEFAULT NULL,
  PRIMARY KEY (`nhom_tc_id`),
  KEY `idx_nhom_tc_template` (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `object_review`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `object_review` (
  `id_obj_review` int NOT NULL AUTO_INCREMENT,
  `target_type` enum('user','group') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_id` int NOT NULL,
  `id_evaluate` int NOT NULL,
  PRIMARY KEY (`id_obj_review`),
  KEY `idx_or_eval` (`id_evaluate`),
  KEY `idx_or_target` (`target_type`,`target_id`),
  CONSTRAINT `fk_or_eval` FOREIGN KEY (`id_evaluate`) REFERENCES `evaluate` (`id_evaluate`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` timestamp NOT NULL,
  `used` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pr_token` (`token`),
  KEY `idx_pr_user` (`user_id`),
  CONSTRAINT `fk_pr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Token ─æß║Àt lß║íi mß║¡t khß║®u';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `peer_review_chat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `peer_review_chat` (
  `id` int NOT NULL AUTO_INCREMENT,
  `consensus_id` int NOT NULL,
  `user_id` int NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_chat_consensus` (`consensus_id`),
  KEY `fk_chat_user` (`user_id`),
  CONSTRAINT `fk_chat_consensus` FOREIGN KEY (`consensus_id`) REFERENCES `peer_review_consensus` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_chat_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `peer_review_consensus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `peer_review_consensus` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_deliver` int NOT NULL COMMENT 'ID of the deliver (reviewer person/group)',
  `response_review` int NOT NULL COMMENT 'ID of the response being reviewed',
  `status` enum('pending','discussing','agreed','timeout') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `final_score` float DEFAULT NULL COMMENT 'Final consensus score',
  `started_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_consensus_deliver_response` (`id_deliver`,`response_review`),
  KEY `fk_consensus_response` (`response_review`),
  CONSTRAINT `fk_consensus_deliver` FOREIGN KEY (`id_deliver`) REFERENCES `deliver` (`id_deliver`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_consensus_response` FOREIGN KEY (`response_review`) REFERENCES `response` (`id_response`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `peer_review_individual_score`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `peer_review_individual_score` (
  `id` int NOT NULL AUTO_INCREMENT,
  `consensus_id` int NOT NULL,
  `user_id` int NOT NULL,
  `score` float NOT NULL,
  `criterion_scores` json DEFAULT NULL COMMENT 'Per-criterion scores',
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `submitted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_score_consensus_user` (`consensus_id`,`user_id`),
  KEY `fk_score_user` (`user_id`),
  CONSTRAINT `fk_score_consensus` FOREIGN KEY (`consensus_id`) REFERENCES `peer_review_consensus` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_score_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `phase`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `phase` (
  `phase_id` int NOT NULL AUTO_INCREMENT,
  `phase_number` int DEFAULT NULL,
  `phase_name` varchar(100) DEFAULT NULL,
  `m_phase_id` int DEFAULT NULL,
  `topic_id` int DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `flag_active` tinyint DEFAULT NULL,
  `added_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`phase_id`),
  KEY `fk_phase_mainphase` (`m_phase_id`),
  KEY `fk_phase_topic` (`topic_id`),
  CONSTRAINT `fk_phase_mainphase` FOREIGN KEY (`m_phase_id`) REFERENCES `main_phase` (`m_phase_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_phase_topic` FOREIGN KEY (`topic_id`) REFERENCES `topic` (`topic_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `points_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `points_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `points` int NOT NULL,
  `reason` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pl_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reading_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reading_list` (
  `list_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`list_id`),
  KEY `idx_rl_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reading_list_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reading_list_item` (
  `id` int NOT NULL AUTO_INCREMENT,
  `list_id` int NOT NULL,
  `task_id` int NOT NULL,
  `added_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_rli` (`list_id`,`task_id`),
  KEY `idx_rli_list` (`list_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reflect_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reflect_detail` (
  `id_reflect_detail` int NOT NULL AUTO_INCREMENT,
  `content` longtext NOT NULL,
  `id_user` int NOT NULL,
  `id_reflect` int NOT NULL,
  `parent_reflect_detail` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_reflect_detail`),
  KEY `fk_reflect_detail_user` (`id_user`),
  KEY `fk_reflect_detail_ticket` (`id_reflect`),
  KEY `fk_reflect_detail_parent` (`parent_reflect_detail`),
  CONSTRAINT `fk_reflect_detail_parent` FOREIGN KEY (`parent_reflect_detail`) REFERENCES `reflect_detail` (`id_reflect_detail`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_reflect_detail_ticket` FOREIGN KEY (`id_reflect`) REFERENCES `reflect_ticket` (`id_reflect`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reflect_detail_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reflect_ticket`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reflect_ticket` (
  `id_reflect` int NOT NULL AUTO_INCREMENT,
  `title` varchar(500) NOT NULL,
  `id_user` int NOT NULL,
  `id_reflect_type` int NOT NULL,
  `status` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_reflect`),
  KEY `fk_reflect_ticket_user` (`id_user`),
  KEY `fk_reflect_ticket_type` (`id_reflect_type`),
  CONSTRAINT `fk_reflect_ticket_type` FOREIGN KEY (`id_reflect_type`) REFERENCES `reflect_type` (`id_reflect_type`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_reflect_ticket_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reflect_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reflect_type` (
  `id_reflect_type` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id_reflect_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `registration_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `registration_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fullname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('student','teacher') COLLATE utf8mb4_unicode_ci DEFAULT 'student',
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `reviewed_by` int DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reject_reason` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reg_username` (`username`),
  KEY `idx_reg_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Y├¬u cß║ºu ─æ─âng k├¢ t├ái khoß║ún';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `response`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `response` (
  `id_response` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `owner_user_id` int DEFAULT NULL,
  `parent_response` int DEFAULT NULL,
  `task_id` int DEFAULT NULL,
  `json_ai` json DEFAULT NULL,
  `tracking_data` json DEFAULT NULL COMMENT 'User behavior tracking data (copy, edit, paste events)',
  `tracking_updated_at` timestamp NULL DEFAULT NULL COMMENT 'Last tracking data update timestamp',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_response`),
  KEY `idx_response_user` (`user_id`),
  KEY `idx_response_parent` (`parent_response`),
  KEY `idx_response_task` (`task_id`),
  KEY `fk_response_owner` (`owner_user_id`),
  KEY `idx_tracking_updated` (`tracking_updated_at`),
  CONSTRAINT `fk_response_owner` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_response_parent` FOREIGN KEY (`parent_response`) REFERENCES `response` (`id_response`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_response_task` FOREIGN KEY (`task_id`) REFERENCES `task` (`task_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_response_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `result_evaluate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `result_evaluate` (
  `id_result_eval` int NOT NULL AUTO_INCREMENT,
  `id_evaluate` int NOT NULL,
  PRIMARY KEY (`id_result_eval`),
  KEY `idx_re_eval` (`id_evaluate`),
  CONSTRAINT `fk_re_eval` FOREIGN KEY (`id_evaluate`) REFERENCES `evaluate` (`id_evaluate`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rubric_level`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rubric_level` (
  `level_id` int NOT NULL AUTO_INCREMENT,
  `tc_id` int NOT NULL COMMENT 'FK to tieu_chi',
  `level_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g., Excellent, Good, Fair, Poor',
  `score` int NOT NULL COMMENT 'Score for this level',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT 'Description of what this level looks like',
  `sort_order` int DEFAULT '0',
  PRIMARY KEY (`level_id`),
  KEY `idx_rubric_level_tc` (`tc_id`),
  CONSTRAINT `fk_rubric_level_tc` FOREIGN KEY (`tc_id`) REFERENCES `tieu_chi` (`tc_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rubric_template`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rubric_template` (
  `template_id` int NOT NULL AUTO_INCREMENT,
  `class_id` int DEFAULT NULL COMMENT 'NULL = global template available to all classes',
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`template_id`),
  KEY `idx_rubric_class` (`class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `score`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `score` (
  `score_id` int NOT NULL AUTO_INCREMENT,
  `id_evaluate` int NOT NULL,
  `grade` decimal(4,2) NOT NULL,
  `id_assignment` int NOT NULL,
  PRIMARY KEY (`score_id`),
  KEY `idx_score_eval` (`id_evaluate`),
  KEY `idx_score_asg` (`id_assignment`),
  CONSTRAINT `fk_score_assignment` FOREIGN KEY (`id_assignment`) REFERENCES `assignment` (`id_assignment`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_score_evaluate` FOREIGN KEY (`id_evaluate`) REFERENCES `evaluate` (`id_evaluate`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `score_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `score_detail` (
  `id_score_detail` int NOT NULL AUTO_INCREMENT,
  `score_id` int NOT NULL,
  `tc_id` int NOT NULL,
  PRIMARY KEY (`id_score_detail`),
  KEY `idx_sd_score` (`score_id`),
  KEY `idx_sd_tc` (`tc_id`),
  CONSTRAINT `fk_sdetail_score` FOREIGN KEY (`score_id`) REFERENCES `score` (`score_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sdetail_tc` FOREIGN KEY (`tc_id`) REFERENCES `tieu_chi` (`tc_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `setting` (
  `id_setting` int NOT NULL AUTO_INCREMENT,
  `task_id` int NOT NULL,
  `number` int DEFAULT NULL,
  `evaluate_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `open_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `is_anonymous` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_setting`),
  KEY `idx_setting_task` (`task_id`),
  CONSTRAINT `fk_setting_task` FOREIGN KEY (`task_id`) REFERENCES `task` (`task_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `setting_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `setting_detail` (
  `id_setting_detail` int NOT NULL AUTO_INCREMENT,
  `id_setting` int NOT NULL,
  `id_obj_review` int DEFAULT NULL,
  PRIMARY KEY (`id_setting_detail`),
  KEY `idx_sd_setting` (`id_setting`),
  CONSTRAINT `fk_sd_setting` FOREIGN KEY (`id_setting`) REFERENCES `setting` (`id_setting`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `streak_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `streak_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `log_date` date NOT NULL,
  `activity_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'login',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_streak_log` (`user_id`,`log_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `task` (
  `task_id` int NOT NULL AUTO_INCREMENT,
  `phase_id` int NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `description` text,
  `deadline` datetime DEFAULT NULL,
  `file_content` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `content_creator` int DEFAULT NULL,
  `review_task` int DEFAULT NULL,
  `added_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`task_id`),
  KEY `fk_users_addedBy` (`added_by`),
  KEY `fk_task_reviewTask` (`review_task`),
  KEY `fk_phase_phaseId` (`phase_id`),
  KEY `fk_user_contentCreator` (`content_creator`),
  CONSTRAINT `fk_phase_phaseId` FOREIGN KEY (`phase_id`) REFERENCES `phase` (`phase_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_task_reviewTask` FOREIGN KEY (`review_task`) REFERENCES `task` (`task_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_user_contentCreator` FOREIGN KEY (`content_creator`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_users_addedBy` FOREIGN KEY (`added_by`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Tasks within a phase';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `task_evaluate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `task_evaluate` (
  `id_task` int NOT NULL,
  `id_group_tc` int NOT NULL,
  UNIQUE KEY `id_task` (`id_task`),
  KEY `fk_group_tc_taskEvaluate` (`id_group_tc`),
  CONSTRAINT `fk_group_tc_taskEvaluate` FOREIGN KEY (`id_group_tc`) REFERENCES `nhom_tc` (`nhom_tc_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_task_taskEvaluate` FOREIGN KEY (`id_task`) REFERENCES `task` (`task_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tieu_chi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tieu_chi` (
  `tc_id` int NOT NULL AUTO_INCREMENT,
  `name_tc` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nhom_tc_id` int DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `min_score` float DEFAULT NULL,
  `max_score` float DEFAULT NULL,
  PRIMARY KEY (`tc_id`),
  KEY `idx_tc_nhom` (`nhom_tc_id`),
  CONSTRAINT `fk_tc_nhom` FOREIGN KEY (`nhom_tc_id`) REFERENCES `nhom_tc` (`nhom_tc_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `topic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `topic` (
  `topic_id` int NOT NULL AUTO_INCREMENT,
  `topic_name` varchar(255) NOT NULL,
  `class_id` int NOT NULL,
  `added_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`topic_id`),
  KEY `class_id` (`class_id`),
  CONSTRAINT `topic_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Learning topics within a class';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `total_score`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `total_score` (
  `id_total` int NOT NULL AUTO_INCREMENT,
  `response_id` int NOT NULL,
  `teacher_grade` int DEFAULT NULL,
  `penalty` int DEFAULT NULL,
  `final_score` int DEFAULT NULL,
  `id_evaluate` int DEFAULT NULL,
  PRIMARY KEY (`id_total`),
  KEY `idx_ts_resp` (`response_id`),
  KEY `idx_ts_eval` (`id_evaluate`),
  CONSTRAINT `fk_ts_eval` FOREIGN KEY (`id_evaluate`) REFERENCES `evaluate` (`id_evaluate`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ts_resp` FOREIGN KEY (`response_id`) REFERENCES `response` (`id_response`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_badge`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_badge` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `badge_id` int NOT NULL,
  `earned_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_badge` (`user_id`,`badge_id`),
  KEY `idx_ub_user` (`user_id`),
  KEY `idx_ub_badge` (`badge_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_notification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_notification` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `link` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_read` tinyint NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`,`is_read`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_points`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_points` (
  `user_id` int NOT NULL,
  `total_points` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_streak`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_streak` (
  `user_id` int NOT NULL,
  `current_streak` int NOT NULL DEFAULT '0',
  `longest_streak` int NOT NULL DEFAULT '0',
  `last_active_date` date DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `fullname` varchar(255) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `role` enum('student','teacher','admin') DEFAULT 'student',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='User management table';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


-- Dữ liệu danh mục bắt buộc (không phải dữ liệu người dùng)
INSERT INTO `main_phase` VALUES (1,'Task execution (AI tools and self-critique)','PHASE_START',1,1,0,0,1,'Learners use AI tools to generate literary analysis content, then critically review the generated content themselves.'),(2,'Reviewing and correcting work','PHASE_CHUABAI',0,1,0,1,1,'Learners and the teacher correct the work by commenting on the parts that need revision.'),(3,'Homework (AI tools)','PHASE_BTVN',1,1,1,0,1,'Learners complete the assigned homework tasks.'),(4,'Peer assessment','PHASE_DANHGIANGUOIHOC',0,1,0,0,1,'Assigned reviewers grade each designated submission.'),(5,'Grading','PHASE_CHAMDIEM',0,1,0,1,0,'The teacher gives the concluding grades for the submissions.'),(6,'Knowledge acquisition','PHASE_TAKENOTE',0,1,0,1,1,'Learners take notes on key knowledge from the teacher\'s in-class lecture, connecting it with the AI analysis done earlier.');
INSERT INTO `reflect_type` VALUES (1,'General feedback');
INSERT INTO `badge` VALUES (1,'First Steps','Complete your first task','bi-check-circle','success','tasks_completed',1,'2026-11-20 11:25:48'),(2,'Task Master','Complete 5 tasks','bi-award','primary','tasks_completed',5,'2026-11-20 11:25:48'),(3,'Task Legend','Complete 10 tasks','bi-trophy','warning','tasks_completed',10,'2026-11-20 11:25:48'),(4,'Peer Reviewer','Complete your first evaluation','bi-star','info','evaluations_done',1,'2026-11-20 11:25:48'),(5,'Expert Reviewer','Complete 5 evaluations','bi-star-fill','primary','evaluations_done',5,'2026-11-20 11:25:48'),(6,'3-Day Streak','Login 3 days in a row','bi-fire','danger','streak_days',3,'2026-11-20 11:25:48'),(7,'7-Day Streak','Login 7 days in a row','bi-fire','warning','streak_days',7,'2026-11-20 11:25:48'),(8,'30-Day Streak','Login 30 days in a row','bi-fire','success','streak_days',30,'2026-11-20 11:25:48');
