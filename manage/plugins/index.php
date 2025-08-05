<?php
require_once dirname(__DIR__, 2) . '/includes/map.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: " . HOME_URL);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['action']) || $_POST['action'] !== 'manual_backup') {
        $new_settings = $_POST['settings'] ?? [];
        $active_tab = $_POST['active_tab'] ?? null;
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            foreach ($new_settings as $key => $value) {
                if (is_array($value)) {
                    $value = json_encode($value);
                }
                $stmt->execute([$key, trim($value)]);
            }
            $pdo->commit();
            
            $_SESSION['settings_update_message'] = $settings_data['settings_msg_update_success'];
            $_SESSION['settings_update_type'] = 'success';

        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['settings_update_message'] = ($settings_data['settings_msg_update_fail'] ?? 'Failed to update settings:') . " " . $e->getMessage();
            $_SESSION['settings_update_type'] = 'error';
        }
    }
    
    $redirect_url = MANAGE_PLUGINS_URL;
    if ($active_tab) {
        $redirect_url .= '?tab=' . urlencode($active_tab);
    }
    header("Location: " . $redirect_url);
    exit();
}

$plugin_settings_files = glob(PROJECT_ROOT . '/ext/*/settings.php');
$plugins_with_settings = [];
foreach ($plugin_settings_files as $file) {
    $plugin_folder = basename(dirname($file));
    $manifest_path = PROJECT_ROOT . "/ext/{$plugin_folder}/plugin.json";
    if (file_exists($manifest_path)) {
        $manifest_data = json_decode(file_get_contents($manifest_path), true);
        $plugins_with_settings[] = [
            'folder' => $plugin_folder,
            'name' => $manifest_data['plugin_name'] ?? ucfirst($plugin_folder),
            'file_path' => $file
        ];
    }
}

$initial_tab = $plugins_with_settings[0]['folder'] ?? '';
if (isset($_GET['tab'])) {
    foreach ($plugins_with_settings as $plugin) {
        if ($plugin['folder'] === $_GET['tab']) {
            $initial_tab = $_GET['tab'];
            break;
        }
    }
}


$page_title = $settings_data['menu_plugin_settings'] . " - " . $settings_data['seo_title'];
require_once HEADER;

$raw_stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
$raw_settings_data = $raw_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$feedback_message = $_SESSION['settings_update_message'] ?? null;
$feedback_type = $_SESSION['settings_update_type'] ?? 'success';
unset($_SESSION['settings_update_message'], $_SESSION['settings_update_type']);
?>

<style>
    .side-menu-button {
        display: block;
        width: 100%;
        padding: 0.75rem 1.25rem;
        text-align: left;
        font-weight: 600;
        color: #d1d5db;
        border-radius: 0.5rem;
        transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out;
    }
    .side-menu-button:hover {
        background-color: #4b5563;
        color: #ffffff;
    }
    .side-menu-button.active {
        background-color: #0ea5e9;
        color: #ffffff;
    }
</style>

<main class="pt-32 pb-20">
    <section id="plugin-settings" data-aos="fade-up">
        <div class="container mx-auto px-6 max-w-6xl">
            <h2 class="text-3xl font-bold text-center mb-10 section-title"><?php echo htmlspecialchars($settings_data['menu_plugin_settings']); ?></h2>
            
            <div class="flex flex-wrap justify-center gap-4 mb-10">
                <a href="<?php echo MANAGE_PLUGINS_URL . 'discover/'; ?>" class="bg-blue-500 text-white font-semibold px-6 py-3 rounded-lg hover:bg-blue-600 transition-colors duration-300"><?php echo htmlspecialchars($settings_data['discover_remote_title']); ?></a>
                <a href="<?php echo MANAGE_PLUGINS_URL . 'upload/'; ?>" class="bg-sky-500 text-white font-semibold px-6 py-3 rounded-lg hover:bg-sky-600 transition-colors duration-300"><?php echo htmlspecialchars($settings_data['upload_title']); ?></a>
                <a href="<?php echo MANAGE_PLUGINS_URL . 'gallery/'; ?>" class="bg-gray-500 text-white font-semibold px-6 py-3 rounded-lg hover:bg-gray-600 transition-colors duration-300"><?php echo htmlspecialchars($settings_data['delete_plugins']); ?></a>
            </div>

            <?php if ($feedback_message): ?>
                <div class="<?php echo $feedback_type === 'success' ? 'bg-green-500/20 text-green-300' : 'bg-red-500/20 text-red-300'; ?> p-4 rounded-lg mb-6">
                    <p><?php echo htmlspecialchars($feedback_message); ?></p>
                </div>
            <?php endif; ?>

            <?php if (empty($plugins_with_settings)): ?>
                <div class="bg-gray-800/50 p-8 rounded-lg shadow-lg">
                    <p class="text-center text-gray-400 py-8"><?php echo htmlspecialchars($settings_data['no_plugins_installed_settings']); ?></p>
                </div>
            <?php else: ?>
                <form action="<?php echo MANAGE_PLUGINS_URL; ?>" method="POST">
                    <div class="flex flex-col md:flex-row gap-8" x-data="{ tab: '<?php echo htmlspecialchars($initial_tab); ?>' }">
                        
                        <input type="hidden" name="active_tab" :value="tab">

                        <div class="md:w-1/4">
                            <div class="bg-gray-800/50 p-4 rounded-lg shadow-lg mb-6">
                                <button type="submit" name="action" value="save_settings" class="w-full bg-sky-500 text-white font-semibold py-3 rounded-lg hover:bg-sky-600"><?php echo htmlspecialchars($settings_data['settings_save_all_btn']); ?></button>
                            </div>
                            <div class="bg-gray-800/50 p-4 rounded-lg shadow-lg">
                                <nav class="flex flex-col space-y-2">
                                    <?php foreach ($plugins_with_settings as $plugin): ?>
                                        <button type="button" @click="tab = '<?php echo $plugin['folder']; ?>'" :class="{ 'active': tab === '<?php echo $plugin['folder']; ?>' }" class="side-menu-button">
                                            <?php echo htmlspecialchars($plugin['name']); ?>
                                        </button>
                                    <?php endforeach; ?>
                                </nav>
                            </div>
                        </div>

                        <div class="md:w-3/4">
                            <div class="bg-gray-800/50 p-8 rounded-lg shadow-lg">
                                <?php foreach ($plugins_with_settings as $plugin): ?>
                                    <div x-show="tab === '<?php echo $plugin['folder']; ?>'" style="display: none;">
                                        <?php include_once $plugin['file_path']; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require_once FOOTER; ?>