<?php
session_start();
require_once dirname(__DIR__, 2) . '/includes/map.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: " . HOME_URL);
    exit();
}

try {
    $errors = [];
    $success_message = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_string'])) {
        $setting_key = $_POST['setting_key'];
        $setting_value = trim($_POST['setting_value']);

        $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");
        if ($stmt->execute([$setting_value, $setting_key])) {
            $_SESSION['success_message'] = $settings_data['library_update_success'];
            header("Location: " . LIBRARY_URL . "?search=" . urlencode($_GET['search'] ?? '') . "&page=" . ($_GET['page'] ?? 1));
            exit();
        } else {
            $errors[] = $settings_data['library_update_fail'];
        }
    }

    if (isset($_SESSION['success_message'])) {
        $success_message = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
    }

    $excluded_keys = [
        'enable_url_rewriting', 'site_language', 'favicon_url', 'contact_email',
        'smtp_host', 'smtp_port', 'smtp_secure', 'smtp_username', 'smtp_password',
        'smtp_from_email', 'smtp_from_name', 'logo_text', 'hero_title',
        'hero_subtitle_typed', 'hero_button_text',
        'contact_subtitle', 'contact_button_text',
        'footer_copyright', 'footer_credits', 'seo_title', 'seo_description', 'seo_keywords',
        'site_title',
        'hero_background_url',
        'about_title',
        'about_content'
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
<script>
    if (history.scrollRestoration) {
        history.scrollRestoration = 'manual';
    }
    window.scrollTo(0, 0);
</script>
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

            <?php if ($total_pages > 1): ?>
                <div class="flex justify-center items-center gap-2 mt-8">
                    <?php
                    $visible_pages = 7;
                    $start_page = max(1, $page - floor($visible_pages / 2));
                    $end_page = min($total_pages, $start_page + $visible_pages - 1);
                    if ($end_page - $start_page + 1 < $visible_pages) {
                        $start_page = max(1, $end_page - $visible_pages + 1);
                    }
                    ?>

                    <?php if ($page > 1): ?>
                        <a href="?search=<?php echo htmlspecialchars($search_term); ?>&page=1" class="px-3 py-2 rounded-lg bg-gray-700 text-gray-300 hover:bg-gray-600 transition-colors duration-200">
                            &laquo;
                        </a>
                        <a href="?search=<?php echo htmlspecialchars($search_term); ?>&page=<?php echo $page - 1; ?>" class="px-3 py-2 rounded-lg bg-gray-700 text-gray-300 hover:bg-gray-600 transition-colors duration-200">
                            &lt;
                        </a>
                    <?php endif; ?>

                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?search=<?php echo htmlspecialchars($search_term); ?>&page=<?php echo $i; ?>" class="px-4 py-2 rounded-lg <?php echo $i === $page ? 'bg-sky-500 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600 transition-colors duration-200'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?search=<?php echo htmlspecialchars($search_term); ?>&page=<?php echo $page + 1; ?>" class="px-3 py-2 rounded-lg bg-gray-700 text-gray-300 hover:bg-gray-600 transition-colors duration-200">
                            &gt;
                        </a>
                        <a href="?search=<?php echo htmlspecialchars($search_term); ?>&page=<?php echo $total_pages; ?>" class="px-3 py-2 rounded-lg bg-gray-700 text-gray-300 hover:bg-gray-600 transition-colors duration-200">
                            &raquo;
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php require_once FOOTER; ?>