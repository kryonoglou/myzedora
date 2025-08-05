<?php

require_once dirname(__DIR__) . '/includes/map.php';

$found_page_data = null;
$plugin_content_file = null;
$request_path = '';
$rewrite_base = '/';

$request_uri = $_SERVER['REQUEST_URI'];

if (strpos($request_uri, $rewrite_base) === 0) {
    $request_path = substr($request_uri, strlen($rewrite_base));
}

if (($qpos = strpos($request_uri, '?')) !== false) {
    $request_uri = substr($request_uri, 0, $qpos);
}

$pretty_url_marker = '/page/';
if (strpos($request_uri, $pretty_url_marker) !== false) {
    $path_start_pos = strpos($request_uri, $pretty_url_marker) + strlen($pretty_url_marker);
    $request_path = substr($request_uri, $path_start_pos);
} 

else {
    $script_name_marker = '/view/page.php';
    if (strpos($request_uri, $script_name_marker) !== false) {
        $path_start_pos = strpos($request_uri, $script_name_marker) + strlen($script_name_marker);
        $request_path = substr($request_uri, $path_start_pos);
    }
}

$request_path = trim($request_path, '/');

if (!empty($request_path)) {
    $parts = explode('/', $request_path, 2);

    if (count($parts) === 2) {
        $plugin_folder_name = basename($parts[0]);
        $page_slug = $parts[1];
        
        $enabled_key = 'plugin_' . $plugin_folder_name . '_enabled';
        if (($settings_data[$enabled_key] ?? '0') === '1') {

            $plugin_manifest_path = __DIR__ . "/../ext/{$plugin_folder_name}/plugin.json";

            if (file_exists($plugin_manifest_path)) {
                $manifest_data = json_decode(file_get_contents($plugin_manifest_path), true);

                if (isset($manifest_data['pages']) && is_array($manifest_data['pages'])) {
                    foreach ($manifest_data['pages'] as $page) {
                        if (isset($page['slug']) && $page['slug'] === $page_slug) {
                            
                            $content_file = __DIR__ . "/../ext/{$plugin_folder_name}/" . basename($page['file']);
                            
                            if (file_exists($content_file)) {
                                $found_page_data = $page;
                                $plugin_content_file = $content_file;
                                break; 
                            }
                        }
                    }
                }
            }
        }
    }
}

if ($found_page_data) {
    $page_title = ($found_page_data['menu_title'] ?? 'Plugin Page') . " - " . ($settings_data['seo_title'] ?? 'myZedora');
    
    require_once HEADER;
    echo '<main class="pt-32 pb-20">';
    include $plugin_content_file;
    echo '</main>';
    require_once FOOTER;

} else {
    http_response_code(404);
    $page_title = "404 Not Found - " . ($settings_data['seo_title'] ?? 'myZedora');

    require_once HEADER;
    echo '<main class="pt-32 pb-20">';
    include NOT_FOUND_PAGE;
    echo '</main>';
    require_once FOOTER;
}
?>
