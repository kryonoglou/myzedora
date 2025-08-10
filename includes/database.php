<?php

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("Database connection not initialized.");
}

$pdo->exec("SET NAMES utf8mb4; SET CHARACTER SET utf8mb4;");

$tables = [
    "users" => [
        "id" => "INT UNSIGNED AUTO_INCREMENT PRIMARY KEY",
        "username" => "VARCHAR(50) NOT NULL UNIQUE",
        "email" => "VARCHAR(100) NOT NULL UNIQUE",
        "password_hash" => "VARCHAR(255) NOT NULL",
        "full_name" => "VARCHAR(100) NULL",
        "bio" => "TEXT NULL",
        "profile_image_url" => "VARCHAR(255) NULL DEFAULT NULL",
        "allow_announcements" => "BOOLEAN NOT NULL DEFAULT 0",
        "is_admin" => "BOOLEAN NOT NULL DEFAULT 0",
        "is_active" => "BOOLEAN NOT NULL DEFAULT 0",
        "activation_token" => "VARCHAR(255) NULL DEFAULT NULL",
        "reset_token" => "VARCHAR(255) NULL DEFAULT NULL",
        "reset_token_expires_at" => "TIMESTAMP NULL DEFAULT NULL",
        "created_at" => "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
        "updated_at" => "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
    ],
    "projects" => [
        "id" => "INT UNSIGNED AUTO_INCREMENT PRIMARY KEY",
        "title" => "VARCHAR(255) NOT NULL",
        "slug" => "VARCHAR(255) NOT NULL UNIQUE COMMENT 'URL-friendly version of the title'",
        "description" => "TEXT NOT NULL",
        "image_url" => "VARCHAR(255) NULL",
        "project_url" => "VARCHAR(255) NULL",
        "technologies" => "VARCHAR(255) NULL COMMENT 'Comma-separated list of tech used'",
        "display_order" => "INT NOT NULL DEFAULT 0",
        "is_published" => "BOOLEAN NOT NULL DEFAULT 1",
        "created_at" => "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
        "updated_at" => "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
    ],
    "posts" => [
        "id" => "INT UNSIGNED AUTO_INCREMENT PRIMARY KEY",
        "user_id" => "INT UNSIGNED NOT NULL",
        "title" => "VARCHAR(255) NOT NULL",
        "slug" => "VARCHAR(255) NOT NULL UNIQUE COMMENT 'URL-friendly version of the title'",
        "content" => "LONGTEXT NOT NULL",
        "excerpt" => "TEXT NULL",
        "comments_enabled" => "BOOLEAN NOT NULL DEFAULT 1",
        "is_published" => "BOOLEAN NOT NULL DEFAULT 0",
        "published_at" => "TIMESTAMP NULL",
        "created_at" => "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
        "updated_at" => "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
    ],
    "gallery" => [
        "id" => "INT UNSIGNED AUTO_INCREMENT PRIMARY KEY",
        "filename" => "VARCHAR(255) NOT NULL",
        "uploaded_at" => "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP"
    ],
    "comments" => [
        "id" => "INT UNSIGNED AUTO_INCREMENT PRIMARY KEY",
        "post_id" => "INT UNSIGNED NOT NULL",
        "user_id" => "INT UNSIGNED NOT NULL",
        "comment_text" => "TEXT NOT NULL",
        "created_at" => "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP"
    ],
    "custom_styles" => [
        "id" => "INT UNSIGNED AUTO_INCREMENT PRIMARY KEY",
        "name" => "VARCHAR(100) NOT NULL COMMENT 'A descriptive name for the style'",
        "css_code" => "MEDIUMTEXT NOT NULL COMMENT 'The actual CSS code'",
        "is_active" => "BOOLEAN NOT NULL DEFAULT 0 COMMENT '1 if this style is currently applied site-wide'",
        "created_at" => "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
        "updated_at" => "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
    ],
    "announcements" => [
        "id" => "INT UNSIGNED AUTO_INCREMENT PRIMARY KEY",
        "subject" => "VARCHAR(255) NOT NULL",
        "message" => "TEXT NOT NULL",
        "last_sent_at" => "TIMESTAMP NULL DEFAULT NULL",
        "created_at" => "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
        "updated_at" => "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
    ],
    "home_buttons" => [
        "id" => "INT UNSIGNED AUTO_INCREMENT PRIMARY KEY",
        "text" => "VARCHAR(255) NOT NULL",
        "url" => "VARCHAR(255) NOT NULL",
        "color" => "VARCHAR(7) NOT NULL DEFAULT '#38bdf8'",
        "new_tab" => "BOOLEAN NOT NULL DEFAULT 0",
        "display_order" => "INT NOT NULL DEFAULT 0"
    ],
    "site_settings" => [
        "id" => "INT UNSIGNED AUTO_INCREMENT PRIMARY KEY",
        "setting_key" => "VARCHAR(100) NOT NULL UNIQUE",
        "setting_value" => "TEXT NULL"
    ]
];

foreach ($tables as $table => $columns) {
    $cols_sql = [];
    foreach ($columns as $name => $def) {
        $cols_sql[] = "`$name` $def";
    }
    $create_sql = "CREATE TABLE IF NOT EXISTS `$table` (" . implode(", ", $cols_sql) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($create_sql);

    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table`");
    $stmt->execute();
    $existing_cols = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($columns as $col_name => $definition) {
        if (!in_array($col_name, $existing_cols)) {
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$col_name` $definition");
        }
    }
}
?>
