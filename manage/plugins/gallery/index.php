<?php
require_once dirname(__DIR__, 3) . '/includes/map.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: " . HOME_URL);
    exit();
}

$page_title = $settings_data['installed_plugins_title'] . " - " . $settings_data['seo_title'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_plugin' && isset($_POST['plugin_folder'])) {
    $plugins_path = PROJECT_ROOT . '/ext';
    $plugin_folder = basename($_POST['plugin_folder']);
    $plugin_path = $plugins_path . '/' . $plugin_folder;

    if (is_dir($plugin_path)) {
        try {
            $pdo->exec("DELETE FROM site_settings WHERE setting_key LIKE 'plugin_" . $plugin_folder . "_%'");
        } catch (Exception $e) { }
        
        function delete_directory($dir) {
            if (!is_dir($dir)) {
                return;
            }
            $files = array_diff(scandir($dir), array('.', '..'));
            foreach ($files as $file) {
                (is_dir("$dir/$file")) ? delete_directory("$dir/$file") : unlink("$dir/$file");
            }
            return rmdir($dir);
        }
        
        if (delete_directory($plugin_path)) {
            $_SESSION['plugin_manage_message'] = $settings_data['plugin_delete_success'] . ' "' . $plugin_folder . '"';
            $_SESSION['plugin_manage_type'] = 'success';
        } else {
            $_SESSION['plugin_manage_message'] = $settings_data['plugin_delete_fail'] . ' "' . $plugin_folder . '"';
            $_SESSION['plugin_manage_type'] = 'error';
        }
    } else {
        $_SESSION['plugin_manage_message'] = $settings_data['plugin_not_found'];
        $_SESSION['plugin_manage_type'] = 'error';
    }

    header("Location: " . MANAGE_PLUGINS_URL . 'gallery/');
    exit();
}

require_once HEADER;

$feedback_message = $_SESSION['plugin_manage_message'] ?? null;
$feedback_type = $_SESSION['plugin_manage_type'] ?? 'success';
unset($_SESSION['plugin_manage_message'], $_SESSION['plugin_manage_type']);

$plugins_path = PROJECT_ROOT . '/ext';
$installed_plugins = [];
if (is_dir($plugins_path)) {
    $plugin_dirs = glob($plugins_path . '/*', GLOB_ONLYDIR);
    foreach ($plugin_dirs as $dir) {
        $manifest_path = $dir . '/plugin.json';
        if (file_exists($manifest_path)) {
            $manifest_data = json_decode(file_get_contents($manifest_path), true);
            $installed_plugins[] = [
                'folder' => basename($dir),
                'name' => $manifest_data['plugin_name'] ?? basename($dir),
                'version' => $manifest_data['version'] ?? 'N/A',
                'description' => $manifest_data['description'] ?? 'No description available.'
            ];
        }
    }
}
?>

<main class="pt-32 pb-20">
    <section id="plugin-gallery" data-aos="fade-up">
        <div class="container mx-auto px-6 max-w-6xl">
            <h2 class="text-3xl font-bold text-center mb-4 section-title"><?php echo htmlspecialchars($settings_data['installed_plugins_title']); ?></h2>
            <p class="text-center text-gray-400 mb-10"><?php echo htmlspecialchars($settings_data['manage_installed']); ?></p>

            <?php if ($feedback_message): ?>
                <div class="<?php echo $feedback_type === 'success' ? 'bg-green-500/20 text-green-300' : 'bg-red-500/20 text-red-300'; ?> p-4 rounded-lg mb-6">
                    <p><?php echo htmlspecialchars($feedback_message); ?></p>
                </div>
            <?php endif; ?>

            <?php if (empty($installed_plugins)): ?>
                <div class="bg-gray-800/50 p-8 rounded-lg shadow-lg">
                    <p class="text-center text-gray-400"><?php echo htmlspecialchars($settings_data['no_plugins_installed']); ?></p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($installed_plugins as $plugin): ?>
                        <div class="bg-gray-800/50 p-6 rounded-lg shadow-lg flex flex-col justify-between">
                            <div>
                                <h3 class="text-xl font-bold text-white mb-2"><?php echo htmlspecialchars($plugin['name']); ?> <span class="text-gray-400 text-sm">(v<?php echo htmlspecialchars($plugin['version']); ?>)</span></h3>
                                <p class="text-gray-400 text-sm mb-4"><?php echo htmlspecialchars($plugin['description']); ?></p>
                            </div>
                            <div class="mt-4">
                                <form action="<?php echo MANAGE_PLUGINS_URL . 'gallery/'; ?>" method="POST" onsubmit="return confirm('<?php echo htmlspecialchars($settings_data['delete_warning']); ?>');">
                                    <input type="hidden" name="action" value="delete_plugin">
                                    <input type="hidden" name="plugin_folder" value="<?php echo htmlspecialchars($plugin['folder']); ?>">
                                    <button type="submit" class="w-full bg-red-600 text-white font-semibold py-2 px-4 rounded-lg hover:bg-red-700">
                                        <?php echo htmlspecialchars($settings_data['delete_button']); ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </section>
</main>

<?php require_once FOOTER; ?>