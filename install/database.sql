SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NULL,
  `bio` TEXT NULL,
  `profile_image_url` VARCHAR(255) NULL DEFAULT NULL,
  `allow_announcements` BOOLEAN NOT NULL DEFAULT 0,
  `is_admin` BOOLEAN NOT NULL DEFAULT 0,
  `is_active` BOOLEAN NOT NULL DEFAULT 0,
  `activation_token` VARCHAR(255) NULL DEFAULT NULL,
  `reset_token` VARCHAR(255) NULL DEFAULT NULL,
  `reset_token_expires_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `activation_token_idx` (`activation_token`),
  INDEX `reset_token_idx` (`reset_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `projects` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE COMMENT 'URL-friendly version of the title',
  `description` TEXT NOT NULL,
  `image_url` VARCHAR(255) NULL,
  `project_url` VARCHAR(255) NULL,
  `technologies` VARCHAR(255) NULL COMMENT 'Comma-separated list of tech used',
  `display_order` INT NOT NULL DEFAULT 0,
  `is_published` BOOLEAN NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `posts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE COMMENT 'URL-friendly version of the title',
  `content` LONGTEXT NOT NULL,
  `excerpt` TEXT NULL,
  `comments_enabled` BOOLEAN NOT NULL DEFAULT 1,
  `is_published` BOOLEAN NOT NULL DEFAULT 0,
  `published_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_posts_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `comments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `post_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `comment_text` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_comments_posts` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_comments_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `statistics` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `visitor_id` VARCHAR(255) NOT NULL,
  `user_id` INT UNSIGNED NULL,
  `page_type` VARCHAR(50) NULL COMMENT 'e.g., post, project, home',
  `item_id` INT UNSIGNED NULL,
  `user_agent` TEXT NULL,
  `is_bot` BOOLEAN NOT NULL DEFAULT 0,
  `view_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `visitor_id_idx` (`visitor_id`),
  INDEX `page_type_item_id_idx` (`page_type`, `item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `custom_styles` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL COMMENT 'A descriptive name for the style',
  `css_code` MEDIUMTEXT NOT NULL COMMENT 'The actual CSS code',
  `is_active` BOOLEAN NOT NULL DEFAULT 0 COMMENT '1 if this style is currently applied site-wide',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES
('enable_url_rewriting', '0'),
('site_language', 'en'),
('favicon_url', '/favicon.ico'),
('contact_email', 'hello@example.com'),
('smtp_host', 'smtp.example.com'),
('smtp_port', '587'),
('smtp_secure', 'tls'),
('smtp_username', 'user@example.com'),
('smtp_password', 'your_smtp_password'),
('smtp_from_email', 'no-reply@example.com'),
('smtp_from_name', 'My Website'),
('registration_mode', '0');
