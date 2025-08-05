<?php
require_once dirname(__DIR__, 3) . '/includes/map.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: " . HOME_URL);
    exit();
}

$page_title = $settings_data['discover_remote_title'] . " - " . $settings_data['seo_title'];

$feedback_message = $_SESSION['plugin_manage_message'] ?? null;
$feedback_type = $_SESSION['plugin_manage_type'] ?? 'success';
unset($_SESSION['plugin_manage_message'], $_SESSION['plugin_manage_type']);

$plugins_path = PROJECT_ROOT . '/ext';
$remote_plugins_list = [];

$remote_url = 'https://www.myzedora.com/get/plugins.json';

try {
    $context = stream_context_create(['http' => ['timeout' => 10]]);
    $json_data = @file_get_contents($remote_url, false, $context);

    if ($json_data !== false) {
        $remote_plugins_list = json_decode($json_data, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($remote_plugins_list)) {
            $remote_plugins_list = [];
            $feedback_message = $settings_data['remote_invalid_json'];
            $feedback_type = 'error';
        }
    } else {
        $feedback_message = $settings_data['remote_fetch_fail'];
        $feedback_type = 'error';
    }
} catch (Exception $e) {
    $feedback_message = $settings_data['remote_fetch_error'] . ': ' . $e->getMessage();
    $feedback_type = 'error';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'download_remote_plugin') {
    $plugin_url = filter_var(trim($_POST['plugin_url']), FILTER_SANITIZE_URL);
    $plugin_name = basename($plugin_url);
    $temp_zip_path = $plugins_path . '/' . $plugin_name;

    if (!filter_var($plugin_url, FILTER_VALIDATE_URL)) {
        $feedback_message = $settings_data['download_url_invalid'];
        $feedback_type = 'error';
    } else {
        try {
            $context = stream_context_create(['http' => ['timeout' => 30]]);
            $zip_content = @file_get_contents($plugin_url, false, $context);

            if ($zip_content === false) {
                $feedback_message = $settings_data['download_fail'] . ' ' . $plugin_name;
                $feedback_type = 'error';
            } else {
                if (file_put_contents($temp_zip_path, $zip_content)) {
                    $zip = new ZipArchive;
                    if ($zip->open($temp_zip_path) === TRUE) {
                        $zip->extractTo($plugins_path);
                        $zip->close();
                        unlink($temp_zip_path);
                        $feedback_message = $settings_data['download_success'] . ' ' . $plugin_name;
                        $feedback_type = 'success';
                    } else {
                        $feedback_message = $settings_data['zip_open_fail'];
                        $feedback_type = 'error';
                    }
                } else {
                    $feedback_message = $settings_data['download_save_fail'] . ' ' . $plugin_name;
                    $feedback_type = 'error';
                }
            }
        } catch (Exception $e) {
            $feedback_message = $settings_data['download_error'] . ': ' . $e->getMessage();
            $feedback_type = 'error';
        }
    }

    if ($feedback_message) {
        $_SESSION['plugin_manage_message'] = $feedback_message;
        $_SESSION['plugin_manage_type'] = $feedback_type;
    }
    header("Location: " . MANAGE_PLUGINS_URL . 'discover/');
    exit();
}

require_once HEADER;

?>

<main class="pt-32 pb-20">
    <section id="discover-plugins" data-aos="fade-up">
        <div class="container mx-auto px-6 max-w-6xl">
            <h2 class="text-3xl font-bold text-center mb-4 section-title"><?php echo htmlspecialchars($settings_data['discover_remote_title']); ?></h2>
            <p class="text-center text-gray-400 mb-10"><?php echo htmlspecialchars($settings_data['discover_remote_desc']); ?></p>

            <?php if ($feedback_message): ?>
                <div class="<?php echo $feedback_type === 'success' ? 'bg-green-500/20 text-green-300' : 'bg-red-500/20 text-red-300'; ?> p-4 rounded-lg mb-6">
                    <p><?php echo htmlspecialchars($feedback_message); ?></p>
                </div>
            <?php endif; ?>

            <?php if (empty($remote_plugins_list)): ?>
                <div class="bg-gray-800/50 p-8 rounded-lg shadow-lg">
                    <p class="text-center text-gray-400"><?php echo htmlspecialchars($settings_data['remote_no_plugins_found'] ?? 'No plugins found. Please check the URL.'); ?></p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($remote_plugins_list as $plugin): ?>
                        <div class="bg-gray-800/50 p-6 rounded-lg shadow-lg flex flex-col justify-between">
                            <div>
                                <h3 class="text-xl font-bold text-white mb-2"><?php echo htmlspecialchars($plugin['plugin_name'] ?? 'Unknown'); ?> <span class="text-gray-400 text-sm">(v<?php echo htmlspecialchars($plugin['version'] ?? 'N/A'); ?>)</span></h3>
                                <p class="text-gray-400 text-sm mb-4"><?php echo htmlspecialchars($plugin['description'] ?? 'No description.'); ?></p>
                                <p class="text-gray-500 text-xs mt-1">Developer: <?php echo htmlspecialchars($plugin['developer'] ?? 'Unknown'); ?></p>
                            </div>
                            <div class="mt-4">
                                <form action="<?php echo MANAGE_PLUGINS_URL . 'discover/'; ?>" method="POST">
                                    <input type="hidden" name="action" value="download_remote_plugin">
                                    <input type="hidden" name="plugin_url" value="<?php echo htmlspecialchars($plugin['download_url'] ?? ''); ?>">
                                    <button type="submit" class="w-full bg-sky-500 text-white font-semibold py-2 px-4 rounded-lg hover:bg-sky-600 transition-colors duration-300" <?php echo empty($plugin['download_url']) ? 'disabled' : ''; ?>>
                                        <?php echo htmlspecialchars($settings_data['download_button']); ?>
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