<?php
$plugin_settings_path = dirname(__DIR__, 2) . '/plugin/*/settings.php';
$plugin_settings_files = glob($plugin_settings_path);

if (empty($plugin_settings_files)) {
    echo '<p class="text-gray-400 text-center py-8">No third-party integrations found.</p>';
} else {
    foreach ($plugin_settings_files as $plugin_settings_file) {
        include $plugin_settings_file;
    }
}
?>