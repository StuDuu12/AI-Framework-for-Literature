-- Extend literary knowledge base: authors + readable full content
-- Apply after literary_canon.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `literary_author` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `name_original` varchar(255) DEFAULT NULL,
  `slug` varchar(120) NOT NULL,
  `region` enum('vietnam','international') NOT NULL DEFAULT 'vietnam',
  `country` varchar(80) DEFAULT NULL,
  `birth_year` varchar(20) DEFAULT NULL,
  `death_year` varchar(20) DEFAULT NULL,
  `era` varchar(80) DEFAULT NULL,
  `biography` text,
  `portrait_image` varchar(500) DEFAULT NULL,
  `wikipedia_url` varchar(500) DEFAULT NULL,
  `keywords` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_la_slug` (`slug`),
  KEY `idx_la_region` (`region`),
  FULLTEXT KEY `ft_la_search` (`name`,`biography`,`keywords`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `literary_work`
  ADD COLUMN IF NOT EXISTS `author_id` int DEFAULT NULL AFTER `author`,
  ADD COLUMN IF NOT EXISTS `full_content` longtext AFTER `description`,
  ADD COLUMN IF NOT EXISTS `content_html` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 if full_content is HTML' AFTER `full_content`,
  ADD KEY IF NOT EXISTS `idx_lw_author` (`author_id`);
