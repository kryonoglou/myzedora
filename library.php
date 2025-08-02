<?php
require_once __DIR__ . '/includes/map.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: " . HOME_URL);
    exit();
}

try {
    $errors = [];
    $success_message = '';

    if (isset($_GET['action']) && $_GET['action'] === 'download_backup') {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
        $all_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $backup_content = json_encode($all_settings, JSON_PRETTY_PRINT);
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="myzedora_settings_backup_' . date('Y-m-d_H-i-s') . '.json"');
        echo $backup_content;
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_backup'])) {
        if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
            $file_content = file_get_contents($_FILES['backup_file']['tmp_name']);
            $backup_data = json_decode($file_content, true);

            if ($backup_data === null || !is_array($backup_data)) {
                $errors[] = $settings_data['library_restore_fail'] . ' ' . $settings_data['library_invalid_file'];
            } else {
                try {
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");

                    foreach ($backup_data as $key => $value) {
                        $stmt->execute([$key, $value]);
                    }

                    $pdo->commit();
                    $success_message = $settings_data['library_restore_success'];
                    
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

                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $errors[] = $settings_data['library_restore_fail'] . ' ' . $e->getMessage();
                }
            }
        } else {
            $errors[] = $settings_data['library_restore_fail'] . ' ' . $settings_data['library_no_file_uploaded'];
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_string'])) {
        $setting_key = $_POST['setting_key'];
        $setting_value = trim($_POST['setting_value']);

        $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");
        if ($stmt->execute([$setting_value, $setting_key])) {
            $success_message = $settings_data['library_update_success'];

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
        } else {
            $errors[] = $settings_data['library_update_fail'];
        }
    }

    $excluded_keys = [
        'enable_url_rewriting', 'site_language', 'favicon_url', 'contact_email',
        'smtp_host', 'smtp_port', 'smtp_secure', 'smtp_username', 'smtp_password',
        'smtp_from_email', 'smtp_from_name', 'logo_text', 'hero_title',
        'hero_subtitle_typed', 'hero_button_text',
        'about_p1', 'about_p2', 'contact_subtitle', 'contact_button_text',
        'footer_copyright', 'footer_credits', 'seo_title', 'seo_description', 'seo_keywords',
        'site_title'
    ];
    $placeholders = implode(',', array_fill(0, count($excluded_keys), '?'));

    $search_term = trim($_GET['search'] ?? '');
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 25;
    $offset = ($page - 1) * $limit;

    $base_query = "FROM site_settings WHERE setting_key NOT IN ($placeholders)";
    $params = $excluded_keys;

    if (!empty($search_term)) {
        $base_query .= " AND (setting_key LIKE ? OR setting_value LIKE ?)";
        $search_param = '%' . $search_term . '%';
        $params[] = $search_param;
        $params[] = $search_param;
    }

    $total_stmt = $pdo->prepare("SELECT COUNT(*) " . $base_query);
    $total_stmt->execute($params);
    $total_items = $total_stmt->fetchColumn();
    $total_pages = ceil($total_items / $limit);

    $params[] = $limit;
    $params[] = $offset;
    $stmt = $pdo->prepare("SELECT setting_key, setting_value " . $base_query . " ORDER BY setting_key ASC LIMIT ? OFFSET ?");
    
    $param_index = 1;
    foreach ($params as $key => &$val) {
        $stmt->bindParam($param_index, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        $param_index++;
    }
    $stmt->execute();
    $strings = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$page_title = $settings_data['library_title'] . " - " . $settings_data['seo_title'];

require_once HEADER;
?>
<main class="pt-32 pb-20">
    <section id="library" data-aos="fade-up">
        <div class="container mx-auto px-6 max-w-5xl">
            <h1 class="text-4xl font-bold text-center mb-4 section-title"><?php echo htmlspecialchars($settings_data['library_title']); ?></h1>
            <p class="text-center text-gray-400 mb-10"><?php echo htmlspecialchars($settings_data['library_subtitle']); ?></p>

            <form method="GET" action="<?php echo LIBRARY_URL; ?>" class="mb-8 max-w-lg mx-auto">
                <div class="relative">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="<?php echo htmlspecialchars($settings_data['library_search_placeholder']); ?>" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-3 px-4 text-white">
                    <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 bg-sky-500 text-white px-4 py-1.5 rounded-md hover:bg-sky-600"><?php echo htmlspecialchars($settings_data['members_search_btn']); ?></button>
                </div>
            </form>

            <?php if ($success_message): ?><div class="bg-green-500/20 text-green-300 p-4 rounded-lg mb-6 max-w-lg mx-auto text-center"><p><?php echo $success_message; ?></p></div><?php endif; ?>
            <?php if (!empty($errors)): ?><div class="bg-red-500/20 text-red-300 p-4 rounded-lg mb-6 max-w-lg mx-auto text-center"><?php foreach($errors as $error) echo "<p>$error</p>"; ?></div><?php endif; ?>

            <div class="bg-gray-800/50 rounded-lg shadow-lg overflow-hidden" x-data="{ editKey: null }">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-900/50">
                            <tr>
                                <th class="p-4 font-semibold text-white w-1/3"><?php echo htmlspecialchars($settings_data['library_key_col']); ?></th>
                                <th class="p-4 font-semibold text-white w-2/3"><?php echo htmlspecialchars($settings_data['library_value_col']); ?></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <?php if (empty($strings)): ?>
                                <tr><td colspan="2" class="p-4 text-center text-gray-400"><?php echo htmlspecialchars($settings_data['library_no_keys_found']); ?></td></tr>
                            <?php else: ?>
                                <?php foreach ($strings as $string): ?>
                                    <tr>
                                        <td class="p-4 text-sky-400 font-mono text-sm"><?php echo htmlspecialchars($string['setting_key']); ?></td>
                                        <td class="p-4 text-white">
                                            <div x-show="editKey !== '<?php echo $string['setting_key']; ?>'" class="flex justify-between items-center">
                                                <span><?php echo htmlspecialchars($string['setting_value']); ?></span>
                                                <button @click="editKey = '<?php echo $string['setting_key']; ?>'" class="text-green-400 hover:text-green-300 font-semibold"><?php echo htmlspecialchars($settings_data['library_edit_btn']); ?></button>
                                            </div>
                                            <form method="POST" action="<?php echo LIBRARY_URL; ?>?search=<?php echo htmlspecialchars($search_term); ?>&page=<?php echo $page; ?>" x-show="editKey === '<?php echo $string['setting_key']; ?>'" style="display: none;">
                                                <input type="hidden" name="setting_key" value="<?php echo $string['setting_key']; ?>">
                                                <textarea name="setting_value" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white" rows="3"><?php echo htmlspecialchars($string['setting_value']); ?></textarea>
                                                <div class="flex gap-2 mt-2">
                                                    <button type="submit" name="update_string" class="bg-green-600 text-white px-3 py-1 text-sm rounded-md hover:bg-green-700"><?php echo htmlspecialchars($settings_data['library_save_btn']); ?></button>
                                                    <button type="button" @click="editKey = null" class="bg-gray-600 text-white px-3 py-1 text-sm rounded-md hover:bg-gray-700"><?php echo htmlspecialchars($settings_data['library_cancel_btn']); ?></button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-center items-center gap-4 mt-8">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?search=<?php echo htmlspecialchars($search_term); ?>&page=<?php echo $i; ?>" class="px-4 py-2 rounded-lg <?php echo $i === $page ? 'bg-sky-500 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>

            <div class="mt-12 p-8 bg-gray-800/50 rounded-lg shadow-lg">
                <h2 class="text-2xl font-bold text-white mb-4"><?php echo htmlspecialchars($settings_data['library_backup_title']); ?></h2>
                <p class="text-gray-400 mb-6"><?php echo htmlspecialchars($settings_data['library_backup_subtitle']); ?></p>

                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <a href="?action=download_backup" class="w-full md:w-auto bg-green-600 text-white font-semibold py-3 px-6 rounded-lg hover:bg-green-700 text-center transition-colors duration-300">
                        <?php echo htmlspecialchars($settings_data['library_download_backup_btn']); ?>
                    </a>
                    
                    <form action="<?php echo LIBRARY_URL; ?>" method="POST" enctype="multipart/form-data" class="w-full md:w-auto flex flex-col md:flex-row items-center gap-4">
                        <input type="hidden" name="restore_backup" value="1">
                        <input type="file" name="backup_file" required class="w-full text-white bg-gray-700 border border-gray-600 rounded-lg py-2 px-4">
                        <button type="submit" onclick="return confirm('<?php echo htmlspecialchars($settings_data['library_restore_warning']); ?>');" class="w-full md:w-auto bg-red-600 text-white font-semibold py-3 px-6 rounded-lg hover:bg-red-700 text-center transition-colors duration-300">
                            <?php echo htmlspecialchars($settings_data['library_restore_backup_btn']); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</main>
<?php require_once FOOTER; ?>