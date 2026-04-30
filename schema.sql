-- 心情便签数据库建表脚本
-- 数据库: mood_notes

CREATE TABLE IF NOT EXISTS `notes` (
  `id` CHAR(36) NOT NULL,
  `content` TEXT NOT NULL,
  `color` VARCHAR(20) NOT NULL DEFAULT '#4a6741',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
