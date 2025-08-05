<?php
require_once dirname(__DIR__, 3) . '/includes/map.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: " . HOME_URL);
    exit();
}

$page_title = $settings_data['upload_title'] . " - " . $settings_data['seo_title'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_plugin') {
    $plugins_path = PROJECT_ROOT . '/ext';
    $zip_file = $_FILES['plugin_zip'];
    $zip_file_path = $plugins_path . '/' . basename($zip_file['name']);

    if ($zip_file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['plugin_manage_message'] = $settings_data['file_upload_fail'] . ' ' . $zip_file['error'] . '.';
        $_SESSION['plugin_manage_type'] = 'error';
    } elseif (pathinfo($zip_file_path, PATHINFO_EXTENSION) !== 'zip') {
        $_SESSION['plugin_manage_message'] = $settings_data['file_type_fail'];
        $_SESSION['plugin_manage_type'] = 'error';
    } else {
        if (move_uploaded_file($zip_file['tmp_name'], $zip_file_path)) {
            $zip = new ZipArchive;
            if ($zip->open($zip_file_path) === TRUE) {
                $zip->extractTo($plugins_path);
                $zip->close();
                unlink($zip_file_path);
                $_SESSION['plugin_manage_message'] = $settings_data['upload_success'];
                $_SESSION['plugin_manage_type'] = 'success';
            } else {
                $_SESSION['plugin_manage_message'] = $settings_data['zip_open_fail'];
                $_SESSION['plugin_manage_type'] = 'error';
            }
        } else {
            $_SESSION['plugin_manage_message'] = $settings_data['upload_move_fail'];
            $_SESSION['plugin_manage_type'] = 'error';
        }
    }
    
    header("Location: " . MANAGE_PLUGINS_URL . 'upload/');
    exit();
}

require_once HEADER;

$feedback_message = $_SESSION['plugin_manage_message'] ?? null;
$feedback_type = $_SESSION['plugin_manage_type'] ?? 'success';
unset($_SESSION['plugin_manage_message'], $_SESSION['plugin_manage_type']);

?>

<main class="pt-32 pb-20">
    <section id="upload-plugin" data-aos="fade-up">
        <div class="container mx-auto px-6 max-w-2xl">
            <h2 class="text-3xl font-bold text-center mb-4 section-title"><?php echo htmlspecialchars($settings_data['upload_title']); ?></h2>
            <p class="text-center text-gray-400 mb-10"><?php echo htmlspecialchars($settings_data['upload_desc']); ?></p>

            <?php if ($feedback_message): ?>
                <div class="<?php echo $feedback_type === 'success' ? 'bg-green-500/20 text-green-300' : 'bg-red-500/20 text-red-300'; ?> p-4 rounded-lg mb-6">
                    <p><?php echo htmlspecialchars($feedback_message); ?></p>
                </div>
            <?php endif; ?>

            <div class="bg-gray-800/50 p-8 rounded-lg shadow-lg">
                <form action="<?php echo MANAGE_PLUGINS_URL . 'upload/'; ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_plugin">
                    <div class="mb-4">
                        <label for="plugin_zip" class="sr-only">Plugin ZIP File</label>
                        <input type="file" name="plugin_zip" id="plugin_zip" accept=".zip" required class="w-full text-white bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-sky-500 file:text-white hover:file:bg-sky-600">
                    </div>
                    <button type="submit" class="w-full bg-sky-500 text-white font-semibold py-3 rounded-lg hover:bg-sky-600"><?php echo htmlspecialchars($settings_data['upload_button']); ?></button>
                </form>
            </div>
        </div>
    </section>
</main>

<?php require_once FOOTER; ?>