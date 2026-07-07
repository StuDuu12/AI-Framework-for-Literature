-- SQL Script: Add tables for Phase 1 (Peer-Review Consensus Gate)
-- Date: 2026-05-15

-- 1. Table for tracking the consensus state of a group evaluation
CREATE TABLE IF NOT EXISTS `peer_review_consensus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_deliver` int(11) NOT NULL COMMENT 'ID của deliver (người/nhóm chấm)',
  `response_review` int(11) NOT NULL COMMENT 'ID của response được chấm',
  `status` enum('pending','discussing','agreed','timeout') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `final_score` float DEFAULT NULL COMMENT 'Điểm đồng thuận cuối cùng',
  `started_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_consensus_deliver_response` (`id_deliver`, `response_review`),
  CONSTRAINT `fk_consensus_deliver` FOREIGN KEY (`id_deliver`) REFERENCES `deliver` (`id_deliver`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_consensus_response` FOREIGN KEY (`response_review`) REFERENCES `response` (`id_response`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table for tracking individual scores before consensus
CREATE TABLE IF NOT EXISTS `peer_review_individual_score` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `consensus_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `score` float NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `submitted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_score_consensus_user` (`consensus_id`, `user_id`),
  CONSTRAINT `fk_score_consensus` FOREIGN KEY (`consensus_id`) REFERENCES `peer_review_consensus` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_score_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table for group discussion chat during consensus
CREATE TABLE IF NOT EXISTS `peer_review_chat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `consensus_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_chat_consensus` (`consensus_id`),
  KEY `fk_chat_user` (`user_id`),
  CONSTRAINT `fk_chat_consensus` FOREIGN KEY (`consensus_id`) REFERENCES `peer_review_consensus` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_chat_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
