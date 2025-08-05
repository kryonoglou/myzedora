<?php

function plugin_lang(string $plugin_folder, string $key): string {
    global $settings_data;
    static $loaded_plugin_langs = [];

    $lang_code = $settings_data['site_language'] ?? 'en';

    if (!isset($loaded_plugin_langs[$plugin_folder][$lang_code])) {
        $lang_file_path = PROJECT_ROOT . "/ext/{$plugin_folder}/languages/{$lang_code}.php";
        
        if (!file_exists($lang_file_path)) {
            $lang_file_path = PROJECT_ROOT . "/ext/{$plugin_folder}/languages/en.php";
        }

        if (file_exists($lang_file_path)) {
            $loaded_plugin_langs[$plugin_folder][$lang_code] = require $lang_file_path;
        } else {
            $loaded_plugin_langs[$plugin_folder][$lang_code] = [];
        }
    }

    return $loaded_plugin_langs[$plugin_folder][$lang_code][$key] ?? $key;
}