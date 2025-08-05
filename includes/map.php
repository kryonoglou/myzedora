<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/version.php';

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


define('PROJECT_ROOT', dirname(__DIR__));
define('INCLUDES_PATH', PROJECT_ROOT . '/includes');
define('ACCESS_PATH', PROJECT_ROOT . '/usr');
define('STYLES_PATH', PROJECT_ROOT . '/manage/styles');
define('MANAGE_PATH', PROJECT_ROOT . '/manage/content/');

define('HEADER', INCLUDES_PATH . '/header.php');
define('FOOTER', INCLUDES_PATH . '/footer.php');
define('NOT_FOUND_PAGE', INCLUDES_PATH . '/404.php');

$base_url = rtrim($_ENV['APP_URL'] ?? '', '/');
$use_pretty_urls = ($settings_data['enable_url_rewriting'] ?? '0') === '1';

if ($use_pretty_urls) {

    #htaccess & nginx needed
    define('SITEMAP_URL', $base_url . '/includes/sitemap.php');
    define('TERMS_URL', $base_url . '/terms/');

    define('LOGIN_URL', $base_url . '/login');
    define('REGISTER_URL', $base_url . '/register');
    define('FORGOT_PASSWORD_URL', $base_url . '/forgot-password/');

    define('PROFILE_URL_BASE', $base_url . '/@');
    define('PROJECT_URL_BASE', $base_url . '/view/project/?slug=');
    define('POST_URL_BASE', $base_url . '/view/post/?slug=');
    define('PROJECTS_PAGE_URL', $base_url . '/view/projects/');
    define('POSTS_PAGE_URL', $base_url . '/view/posts/');
    define('MEMBERS_PLIST_URL', $base_url . '/view/members/');

    //Main
    define('HOME_URL', $base_url . '/');
    define('EDIT_PROFILE_URL', $base_url . '/usr/edit_profile/');
    define('CHANGE_PASSWORD_URL', $base_url . '/usr/password/');
    define('ACTIVATE_URL_BASE', $base_url . '/usr/activate/');
    define('RESET_PASSWORD_URL_BASE', $base_url . '/usr/reset_password/');
    define('ERASE_ACCOUNT_URL', $base_url . '/usr/erase/');
    define('LOGOUT_URL', $base_url . '/usr/logout/');
    define('MANAGE_URL', $base_url . '/manage/');
    define('MANAGE_CONTENT_URL', $base_url . '/manage/content/');
    define('ADD_POST_URL', $base_url . '/manage/content/add_post/');
    define('ADD_PROJECT_URL', $base_url . '/manage/content/add_project/');
    define('DELETE_ITEM_URL_BASE', $base_url . '/manage/content/delete_item/');
    define('SETTINGS_URL', $base_url . '/manage/settings/');
    define('LIBRARY_URL', $base_url . '/manage/library/');
    define('SEND_ANNOUNCEMENTS_URL', $base_url . '/manage/announcements/');
    define('BACKUP_URL', $base_url . '/includes/backup.php');
    define('MANAGE_BACKUP_URL', $base_url . '/manage/content/backup.php');
    define('MANAGE_HOME_URL', $base_url . '/manage/home/');
    define('STYLES_URL', $base_url . '/manage/styles/');
    define('ADD_STYLE_URL', $base_url . '/manage/styles/add/');
    define('STYLES_BACKUP_URL', $base_url . '/manage/styles/backup/');
    define('MANAGE_PLUGINS_URL', $base_url . '/manage/plugins/');
    define('MEMBERS_LIST_URL', $base_url . '/manage/members/');
    define('ADD_ANNOUNCEMENT_URL', $base_url . '/manage/announcements/new/');
    
    define('EDIT_POST_URL_BASE', $base_url . '/manage/content/edit_post/?id=');
    define('EDIT_PROJECT_URL_BASE', $base_url . '/manage/content/edit_project/?id=');
    define('DELETE_USER_URL_BASE', $base_url . '/manage/members/delete_user.php?id=');
    define('EDIT_STYLE_URL_BASE', $base_url . '/manage/styles/edit/?id=');
    define('DELETE_STYLE_URL_BASE', $base_url . '/manage/styles/delete/?id=');
    define('EDIT_ANNOUNCEMENT_URL_BASE', $base_url . '/manage/announcements/edit/?id=');
    define('DELETE_ANNOUNCEMENT_URL_BASE', $base_url . '/manage/announcements/delete/?id=');
    define('SEND_ANNOUNCEMENT_URL_BASE', $base_url . '/manage/announcements/send/?id=');
} else {

    #htaccess & nginx needed
    define('SITEMAP_URL', $base_url . '/includes/sitemap.php');
    define('TERMS_URL', $base_url . '/view/terms/');

    define('LOGIN_URL', $base_url . '/usr/login/');
    define('REGISTER_URL', $base_url . '/usr/register/');
    define('FORGOT_PASSWORD_URL', $base_url . '/usr/forgot_password/');

    define('PROFILE_URL_BASE', $base_url . '/usr/profile/?username=');
    define('PROJECT_URL_BASE', $base_url . '/view/project/?slug=');
    define('POST_URL_BASE', $base_url . '/view/post/?slug=');
    define('PROJECTS_PAGE_URL', $base_url . '/view/projects/');
    define('POSTS_PAGE_URL', $base_url . '/view/posts/');
    define('MEMBERS_PLIST_URL', $base_url . '/view/members/');

    //Main

    define('HOME_URL', $base_url . '/');
    define('EDIT_PROFILE_URL', $base_url . '/usr/edit_profile/');
    define('CHANGE_PASSWORD_URL', $base_url . '/usr/password/');
    define('ACTIVATE_URL_BASE', $base_url . '/usr/activate/');
    define('RESET_PASSWORD_URL_BASE', $base_url . '/usr/reset_password/');
    define('ERASE_ACCOUNT_URL', $base_url . '/usr/erase/');
    define('LOGOUT_URL', $base_url . '/usr/logout/');
    define('MANAGE_URL', $base_url . '/manage/');
    define('MANAGE_CONTENT_URL', $base_url . '/manage/content/');
    define('ADD_POST_URL', $base_url . '/manage/content/add_post/');
    define('ADD_PROJECT_URL', $base_url . '/manage/content/add_project/');
    define('DELETE_ITEM_URL_BASE', $base_url . '/manage/content/delete_item/');
    define('SETTINGS_URL', $base_url . '/manage/settings/');
    define('LIBRARY_URL', $base_url . '/manage/library/');
    define('SEND_ANNOUNCEMENTS_URL', $base_url . '/manage/announcements/');
    define('BACKUP_URL', $base_url . '/includes/backup.php');
    define('MANAGE_BACKUP_URL', $base_url . '/manage/content/backup.php');
    define('MANAGE_HOME_URL', $base_url . '/manage/home/');
    define('STYLES_URL', $base_url . '/manage/styles/');
    define('ADD_STYLE_URL', $base_url . '/manage/styles/add/');
    define('STYLES_BACKUP_URL', $base_url . '/manage/styles/backup/');
    define('MANAGE_PLUGINS_URL', $base_url . '/manage/plugins/');
    define('MEMBERS_LIST_URL', $base_url . '/manage/members/');
    define('ADD_ANNOUNCEMENT_URL', $base_url . '/manage/announcements/new/');
    
    define('EDIT_POST_URL_BASE', $base_url . '/manage/content/edit_post/?id=');
    define('EDIT_PROJECT_URL_BASE', $base_url . '/manage/content/edit_project/?id=');
    define('DELETE_USER_URL_BASE', $base_url . '/manage/members/delete_user.php?id=');
    define('EDIT_STYLE_URL_BASE', $base_url . '/manage/styles/edit/?id=');
    define('DELETE_STYLE_URL_BASE', $base_url . '/manage/styles/delete/?id=');
    define('EDIT_ANNOUNCEMENT_URL_BASE', $base_url . '/manage/announcements/edit/?id=');
    define('DELETE_ANNOUNCEMENT_URL_BASE', $base_url . '/manage/announcements/delete/?id=');
    define('SEND_ANNOUNCEMENT_URL_BASE', $base_url . '/manage/announcements/send/?id=');
}

require_once __DIR__ . '/hooks.php';
require_once __DIR__ . '/shortcodes.php';
require_once __DIR__ . '/plugin_helpers.php';

$plugin_files = glob(PROJECT_ROOT . '/ext/*/*.php');
foreach ($plugin_files as $plugin_file) {
    $plugin_folder_name = basename(dirname($plugin_file));
    $expected_file_name = $plugin_folder_name . '.php';

    if (basename($plugin_file) === $expected_file_name) {
        $enabled_key = 'plugin_' . $plugin_folder_name . '_enabled';
        if (($settings_data[$enabled_key] ?? '0') === '1') {
            require_once $plugin_file;
        }
    }
}

do_action('init');