<?php
require_once dirname(__DIR__) . '/includes/map.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: " . HOME_URL);
    exit();
}

$errors = [];
$success_message = '';
$table_to_backup = 'custom_styles';

if (isset($_GET['action']) && $_GET['action'] === 'download_backup') {
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="myzedora_styles_backup_' . date('Y-m-d_H-i-s') . '.sql"');
    
    try {
        $output = '';
        $pdo->beginTransaction();

        $output .= "--\n-- Table structure for `$table_to_backup`\n--\n";
        $create_stmt = $pdo->query("SHOW CREATE TABLE `$table_to_backup`")->fetch(PDO::FETCH_NUM);
        $output .= $create_stmt[1] . ";\n\n";

        $output .= "--\n-- Dumping data for table `$table_to_backup`\n--\n";
        $data_stmt = $pdo->query("SELECT * FROM `$table_to_backup`");
        while ($row = $data_stmt->fetch(PDO::FETCH_ASSOC)) {
            $output .= "INSERT INTO `$table_to_backup` (";
            $output .= implode(", ", array_map(function($key) { return "`$key`"; }, array_keys($row)));
            $output .= ") VALUES (";
            $output .= implode(", ", array_map(function($value) use ($pdo) {
                return $pdo->quote($value);
            }, $row));
            $output .= ");\n";
        }
        $output .= "\n";

        $pdo->commit();
        echo $output;
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Database Error: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_backup'])) {
    if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
        if (strtolower(pathinfo($_FILES['backup_file']['name'], PATHINFO_EXTENSION)) !== 'sql') {
            $errors[] = $settings_data['styles_backup_restore_fail'];
        } else {
            try {
                $sql_content = file_get_contents($_FILES['backup_file']['tmp_name']);
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
                $pdo->exec("DROP TABLE IF EXISTS `$table_to_backup`;");
                $pdo->exec($sql_content);
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
                $success_message = $settings_data['styles_backup_restore_success'];
            } catch (PDOException $e) {
                $errors[] = $settings_data['styles_backup_restore_fail'] . ' ' . $e->getMessage();
            }
        }
    } else {
        $errors[] = $settings_data['styles_backup_restore_fail'];
    }
}

$page_title = $settings_data['styles_backup_title'] . " - " . $settings_data['seo_title'];

require_once HEADER;
?>

<main class="pt-32 pb-20">
    <section id="styles-backup" data-aos="fade-up">
        <div class="container mx-auto px-6 max-w-3xl">
            <h1 class="text-4xl font-bold text-center mb-4 section-title"><?php echo htmlspecialchars($settings_data['styles_backup_title']); ?></h1>
            <p class="text-center text-gray-400 mb-10"><?php echo htmlspecialchars($settings_data['styles_backup_subtitle']); ?></p>

            <?php if ($success_message): ?><div class="bg-green-500/20 text-green-300 p-4 rounded-lg mb-6 max-w-lg mx-auto text-center"><p><?php echo $success_message; ?></p></div><?php endif; ?>
            <?php if (!empty($errors)): ?><div class="bg-red-500/20 text-red-300 p-4 rounded-lg mb-6 max-w-lg mx-auto text-center"><?php foreach($errors as $error) echo "<p>$error</p>"; ?></div><?php endif; ?>
            
            <div class="mt-8 p-8 bg-gray-800/50 rounded-lg shadow-lg">
                <h2 class="text-2xl font-bold text-white mb-4"><?php echo htmlspecialchars($settings_data['styles_backup_download_btn']); ?></h2>
                <p class="text-gray-400 mb-6"><?php echo htmlspecialchars($settings_data['styles_backup_download_info']); ?></p>
                <a href="<?php echo STYLES_BACKUP_URL; ?>?action=download_backup" class="w-full md:w-auto bg-green-600 text-white font-semibold py-3 px-6 rounded-lg hover:bg-green-700 text-center transition-colors duration-300">
                    <?php echo htmlspecialchars($settings_data['styles_backup_download_btn']); ?>
                </a>
            </div>

            <div class="mt-8 p-8 bg-gray-800/50 rounded-lg shadow-lg">
                <h2 class="text-2xl font-bold text-white mb-4"><?php echo htmlspecialchars($settings_data['styles_backup_restore_title']); ?></h2>
                <p class="text-gray-400 mb-6"><?php echo htmlspecialchars($settings_data['styles_backup_restore_info']); ?></p>
                
                <div class="bg-red-500/20 text-red-300 p-4 rounded-lg mb-6">
                    <p><?php echo htmlspecialchars($settings_data['styles_backup_restore_warning']); ?></p>
                </div>

                <form action="<?php echo STYLES_BACKUP_URL; ?>" method="POST" enctype="multipart/form-data" class="w-full flex flex-col md:flex-row items-center gap-4">
                    <input type="hidden" name="restore_backup" value="1">
                    <input type="file" name="backup_file" required class="w-full text-white bg-gray-700 border border-gray-600 rounded-lg py-2 px-4">
                    <button type="submit" class="w-full md:w-auto bg-red-600 text-white font-semibold py-3 px-6 rounded-lg hover:bg-red-700 text-center transition-colors duration-300" onclick="return confirm('<?php echo htmlspecialchars($settings_data['styles_backup_restore_warning']); ?>');">
                        <?php echo htmlspecialchars($settings_data['styles_backup_restore_btn']); ?>
                    </button>
                </form>
            </div>
             <div class="mt-12 text-center">
                <a href="<?php echo STYLES_URL; ?>" class="text-sky-400 hover:underline">&larr; Back to Styles List</a>
            </div>
        </div>
    </section>
</main>
<?php require_once FOOTER; ?>