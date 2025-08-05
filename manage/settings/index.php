<?php
session_start();
require_once dirname(__DIR__, 2) . '/includes/map.php';

function parseEnvFile($filePath) {
    $env = [];
    if (file_exists($filePath)) {
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            list($key, $value) = explode('=', $line, 2);
            $env[trim($key)] = trim($value, " \n\r\t\"'");
        }
    }
    return $env;
}

function updateEnvFile($filePath, $newVars) {
    if (!file_exists($filePath) || !is_writable($filePath)) {
        return false;
    }
    
    $fileContent = file_get_contents($filePath);
    foreach ($newVars as $key => $value) {
        $escapedValue = '"' . addslashes($value) . '"';
        if (strpos($fileContent, $key . '=') !== false) {
            $fileContent = preg_replace("/^" . preg_quote($key) . "=.*$/m", $key . "=" . $escapedValue, $fileContent);
        } else {
            $fileContent .= "\n" . $key . "=" . $escapedValue;
        }
    }
    
    return file_put_contents($filePath, $fileContent) !== false;
}

$env_file_path = dirname(__DIR__, 2) . '/.env';
$env_vars = parseEnvFile($env_file_path);

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: " . HOME_URL);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_settings = $_POST['settings'];
    $active_tab = $_POST['active_tab'] ?? 'general';
    
    $new_settings['enable_tinymce'] = isset($new_settings['enable_tinymce']) ? '1' : '0';

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        foreach ($new_settings as $key => $value) {
            $stmt->execute([$key, trim($value)]);
        }
        $pdo->commit();
        
        $new_env_vars_to_save = [];
        $new_env_vars_to_save['APP_URL'] = trim($_POST['app_url'] ?? '');
        $app_env_value = ($_POST['settings_app_env'] ?? 'production') === 'development' ? 'development' : 'production';
        $new_env_vars_to_save['APP_ENV'] = $app_env_value;
        
        if (!updateEnvFile($env_file_path, $new_env_vars_to_save)) {
             throw new Exception("Could not write to .env file. Check file permissions.");
        }

        $_SESSION['settings_update_message'] = $settings_data['settings_msg_update_success'];
        $_SESSION['settings_update_type'] = 'success';

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['settings_update_message'] = ($settings_data['settings_msg_update_fail'] ?? 'Failed to update settings:') . " " . $e->getMessage();
        $_SESSION['settings_update_type'] = 'error';
    }

    $redirect_url = SETTINGS_URL . '?tab=' . urlencode($active_tab);
    header("Location: " . $redirect_url);
    exit();
}

$page_title = $settings_data['menu_site_settings'] . " - " . $settings_data['seo_title'];

require_once HEADER;
?>
<script>
    if (history.scrollRestoration) {
        history.scrollRestoration = 'manual';
    }
    window.scrollTo(0, 0);
</script>
<?php
$raw_stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
$raw_settings_data = $raw_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$allowed_tabs = ['general', 'api', 'seo', 'smtp', 'status'];
$initial_tab = 'general';
if (isset($_GET['tab']) && in_array($_GET['tab'], $allowed_tabs)) {
    $initial_tab = $_GET['tab'];
}

$feedback_message = $_SESSION['settings_update_message'] ?? null;
$feedback_type = $_SESSION['settings_update_type'] ?? 'success';
unset($_SESSION['settings_update_message'], $_SESSION['settings_update_type']);
?>

<style>
    .tab-button {
        padding: 0.75rem 1.5rem;
        cursor: pointer;
        background-color: #374151;
        color: #d1d5db;
        border-radius: 0.5rem;
        font-weight: 600;
        transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out;
    }
    .tab-button:hover {
        background-color: #4b5563;
    }
    .tab-button.active {
        background-color: #0ea5e9;
        color: #ffffff;
    }
</style>

<main class="pt-32 pb-20">
    <section id="settings" data-aos="fade-up">
        <div class="container mx-auto px-6 max-w-4xl">
            <div class="bg-gray-800/50 p-8 rounded-lg shadow-lg" x-data="{ tab: '<?php echo htmlspecialchars($initial_tab); ?>' }">
                <h2 class="text-3xl font-bold text-center mb-8 section-title"><?php echo htmlspecialchars($settings_data['menu_site_settings']); ?></h2>

                <div class="flex flex-wrap items-center justify-center gap-4 mb-8 border-b border-gray-700 pb-4">
                    <button type="button" @click="tab = 'general'" :class="{ 'active': tab === 'general' }" class="tab-button">
                        <?php echo htmlspecialchars($settings_data['settings_tab_general']); ?>
                    </button>
                    <button type="button" @click="tab = 'api'" :class="{ 'active': tab === 'api' }" class="tab-button">
                        <?php echo htmlspecialchars($settings_data['settings_tab_api']); ?>
                    </button>
                    <button type="button" @click="tab = 'seo'" :class="{ 'active': tab === 'seo' }" class="tab-button">
                        <?php echo htmlspecialchars($settings_data['settings_tab_seo']); ?>
                    </button>
                    <button type="button" @click="tab = 'smtp'" :class="{ 'active': tab === 'smtp' }" class="tab-button">
                        <?php echo htmlspecialchars($settings_data['settings_tab_smtp']); ?>
                    </button>
                    <button type="button" @click="tab = 'status'" :class="{ 'active': tab === 'status' }" class="tab-button">
                        <?php echo htmlspecialchars($settings_data['settings_tab_status']); ?>
                    </button>
                </div>

                <?php if ($feedback_message): ?>
                    <div class="<?php echo $feedback_type === 'success' ? 'bg-green-500/20 text-green-300' : 'bg-red-500/20 text-red-300'; ?> p-4 rounded-lg mb-6">
                        <p><?php echo htmlspecialchars($feedback_message); ?></p>
                    </div>
                <?php endif; ?>

                <form action="<?php echo SETTINGS_URL; ?>" method="POST">
                    
                    <input type="hidden" name="active_tab" :value="tab">

                    <div x-show="tab === 'general'" class="space-y-8">
                        <?php 
                        include_once __DIR__ . '/general.php'; 
                        ?>
                    </div>

                    <div x-show="tab === 'api'" style="display: none;">
                       <?php include_once __DIR__ . '/api.php'; ?>
                    </div>
                    
                    <div x-show="tab === 'seo'" style="display: none;">
                        <?php include_once __DIR__ . '/seo.php'; ?>
                    </div>
                    
                    <div x-show="tab === 'smtp'" style="display: none;">
                        <?php include_once __DIR__ . '/smtp.php'; ?>
                    </div>
    
                    <div x-show="tab === 'status'" style="display: none;">
                        <?php include_once __DIR__ . '/status.php'; ?>
                    </div>

                    <div class="mt-8" x-show="tab !== 'status'">
                        <button type="submit" class="w-full bg-sky-500 text-white font-semibold py-3 rounded-lg hover:bg-sky-600"><?php echo htmlspecialchars($settings_data['settings_save_all_btn']); ?></button>
                    </div>

                </form>
            </div>
        </div>
    </section>
</main>

<?php require_once FOOTER; ?>