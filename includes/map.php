<?php

require_once __DIR__ . '/config.php';
$settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
$settings_data = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

if (isset($settings_data['site_title'])) {
    $site_title = $settings_data['site_title'];
    foreach ($settings_data as $key => $value) {
        if (is_string($value)) {
            $settings_data[$key] = str_replace('{{site_title}}', $site_title, $value);
        }
    }
}

require_once __DIR__ . '/smtp.php';
require_once __DIR__ . '/log_visit.php';

define('PROJECT_ROOT', dirname(__DIR__));
define('INCLUDES_PATH', PROJECT_ROOT . '/includes');
define('ACCESS_PATH', PROJECT_ROOT . '/usr');
define('STYLES_PATH', PROJECT_ROOT . '/styles');
define('MANAGE_PATH', PROJECT_ROOT . '/manage/');
define('STATS_PATH', PROJECT_ROOT . '/statistics');

define('HEADER', INCLUDES_PATH . '/header.php');
define('FOOTER', INCLUDES_PATH . '/footer.php');
define('NOT_FOUND_PAGE', INCLUDES_PATH . '/404.php');

$base_url = rtrim($_ENV['APP_URL'] ?? '', '/');
$use_pretty_urls = ($settings_data['enable_url_rewriting'] ?? '0') === '1';

if ($use_pretty_urls) {
    define('HOME_URL', $base_url . '/');
    define('LOGIN_URL', $base_url . '/login');
    define('REGISTER_URL', $base_url . '/register');
    define('LOGOUT_URL', $base_url . '/logout');
    define('ACTIVATE_URL_BASE', $base_url . '/activate');
    define('FORGOT_PASSWORD_URL', $base_url . '/forgot-password');
    define('RESET_PASSWORD_URL_BASE', $base_url . '/reset-password');
    define('PROFILE_URL_BASE', $base_url . '/@');
    define('EDIT_PROFILE_URL', $base_url . '/edit-profile');
    define('CHANGE_PASSWORD_URL', $base_url . '/change-password');
    define('ERASE_ACCOUNT_URL', $base_url . '/erase-account');
    define('POST_URL_BASE', $base_url . '/post/');
    define('PROJECT_URL_BASE', $base_url . '/project/');
    define('MANAGE_URL', $base_url . '/manage/');
    define('ADD_POST_URL', $base_url . '/manage/add-post');
    define('ADD_PROJECT_URL', $base_url . '/manage/add-project');
    define('EDIT_POST_URL_BASE', $base_url . '/manage/edit-post/');
    define('EDIT_PROJECT_URL_BASE', $base_url . '/manage/edit-project/');
    define('DELETE_ITEM_URL_BASE', $base_url . '/manage/delete/');
    define('DELETE_USER_URL_BASE', $base_url . '/manage/delete-user/');
    define('SETTINGS_URL', $base_url . '/settings');
    define('STATS_URL', $base_url . '/statistics');
    define('MEMBERS_LIST_URL', $base_url . '/statistics/members');
    define('FULL_STATS_URL', $base_url . '/statistics/full-report');
    define('POSTS_PAGE_URL', $base_url . '/posts');
    define('SITEMAP_URL', $base_url . '/sitemap.xml');
    define('LIBRARY_URL', $base_url . '/library');
    define('SEND_ANNOUNCEMENTS_URL', $base_url . '/send-announcements');
    define('BACKUP_URL', $base_url . '/backup');
    define('MANAGE_BACKUP_URL', $base_url . '/manage/backup');
    define('TERMS_URL', $base_url . '/terms');
    define('STYLES_URL', $base_url . '/styles');
    define('ADD_STYLE_URL', $base_url . '/styles/add');
    define('EDIT_STYLE_URL_BASE', $base_url . '/styles/edit/');
    define('DELETE_STYLE_URL_BASE', $base_url . '/styles/delete/');
    define('STYLES_BACKUP_URL', $base_url . '/styles/backup');
} else {
    define('HOME_URL', $base_url . '/');
    define('LOGIN_URL', $base_url . '/usr/login.php');
    define('REGISTER_URL', $base_url . '/usr/register.php');
    define('LOGOUT_URL', $base_url . '/usr/logout.php');
    define('ACTIVATE_URL_BASE', $base_url . '/usr/activate.php');
    define('FORGOT_PASSWORD_URL', $base_url . '/usr/forgot_password.php');
    define('RESET_PASSWORD_URL_BASE', $base_url . '/usr/reset_password.php');
    define('PROFILE_URL_BASE', $base_url . '/usr/profile.php?username=');
    define('EDIT_PROFILE_URL', $base_url . '/usr/edit_profile.php');
    define('CHANGE_PASSWORD_URL', $base_url . '/usr/password.php');
    define('ERASE_ACCOUNT_URL', $base_url . '/usr/erase.php');
    define('POST_URL_BASE', $base_url . '/post/index.php?slug=');
    define('PROJECT_URL_BASE', $base_url . '/project/index.php?slug=');
    define('MANAGE_URL', $base_url . '/manage/');
    define('ADD_POST_URL', $base_url . '/manage/add_post.php');
    define('ADD_PROJECT_URL', $base_url . '/manage/add_project.php');
    define('EDIT_POST_URL_BASE', $base_url . '/manage/edit_post.php?id=');
    define('EDIT_PROJECT_URL_BASE', $base_url . '/manage/edit_project.php?id=');
    define('DELETE_ITEM_URL_BASE', $base_url . '/manage/delete_item.php');
    define('DELETE_USER_URL_BASE', $base_url . '/manage/delete_user.php?id=');
    define('SETTINGS_URL', $base_url . '/settings.php');
    define('STATS_URL', $base_url . '/statistics/');
    define('MEMBERS_LIST_URL', $base_url . '/statistics/members.php');
    define('FULL_STATS_URL', $base_url . '/statistics/full.php');
    define('POSTS_PAGE_URL', $base_url . '/posts.php');
    define('SITEMAP_URL', $base_url . '/includes/sitemap.php');
    define('LIBRARY_URL', $base_url . '/library.php');
    define('SEND_ANNOUNCEMENTS_URL', $base_url . '/sendtoall.php');
    define('BACKUP_URL', $base_url . '/includes/backup.php');
    define('MANAGE_BACKUP_URL', $base_url . '/manage/backup.php');
    define('TERMS_URL', $base_url . '/usr/terms.php');
    define('STYLES_URL', $base_url . '/styles/index.php');
    define('ADD_STYLE_URL', $base_url . '/styles/add.php');
    define('EDIT_STYLE_URL_BASE', $base_url . '/styles/edit.php?id=');
    define('DELETE_STYLE_URL_BASE', $base_url . '/styles/delete.php?id=');
    define('STYLES_BACKUP_URL', $base_url . '/styles/backup.php');
}