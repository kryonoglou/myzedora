<?php
// myZedora CMS - One-Click Updater

ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

$lock_file_path = __DIR__ . '/.updatelock';

if (file_exists($lock_file_path)) {
    $message = "Update has already been completed. For security, this script is locked. Please DELETE the '/update/' directory from your server.";
    die("<!DOCTYPE html><html><head><title>Updater Locked</title><style>body{background:#111827;color:#e5e7eb;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;padding:2rem;text-align:center;}</style></head><body><div><h1>Updater Locked</h1><p>{$message}</p></div></body></html>");
}

if (!file_exists(dirname(__DIR__) . '/includes/config.php')) {
    die("Configuration file not found. Please ensure the '/update/' directory is inside your main myZedora folder and that the application is already installed.");
}

require_once dirname(__DIR__) . '/includes/config.php';

// --- Helper Functions ---

function tableExists(PDO $pdo, $tableName) {
    try {
        $result = $pdo->query("SELECT 1 FROM `$tableName` LIMIT 1");
    } catch (Exception $e) {
        return false;
    }
    return $result !== false;
}

function columnExists(PDO $pdo, $tableName, $columnName) {
    if (!tableExists($pdo, $tableName)) return false;
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$tableName` LIKE ?");
    $stmt->execute([$columnName]);
    return $stmt->fetch() !== false;
}

// --- Update Logic ---

$logs = [];
$errors = [];
$update_successful = false;
$step = 1;

$languages_dir = __DIR__ . '/languages/';
$available_languages = [];
if (is_dir($languages_dir)) {
    foreach (glob($languages_dir . '*.php') as $file) {
        $lang_code = basename($file, '.php');
        $available_languages[$lang_code] = strtoupper($lang_code);
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_update'])) {
    $step = 2;
    $selected_lang_code = $_POST['language'] ?? 'en';

    if (!isset($available_languages[$selected_lang_code])) {
        $errors[] = "Invalid language selected.";
    } else {
        try {
            // REMOVED: The single, overarching transaction is removed to prevent conflicts.

            // 1. Process database.sql
            $logs[] = "--- Starting Database Schema Update ---";
            $sql_file = __DIR__ . '/database.sql';
            if (file_exists($sql_file)) {
                $sql_content = file_get_contents($sql_file);
                $sql_statements = explode(';', $sql_content);

                foreach ($sql_statements as $statement) {
                    $statement = trim($statement);
                    if (empty($statement)) continue;

                    try {
                        if (preg_match('/^CREATE TABLE `?(\w+)`?/i', $statement, $matches)) {
                            $tableName = $matches[1];
                            if (!tableExists($pdo, $tableName)) {
                                $pdo->exec($statement);
                                $logs[] = "<span style='color: #22c55e;'>SUCCESS:</span> Table '{$tableName}' created.";
                            } else {
                                $logs[] = "<span style='color: #f59e0b;'>INFO:</span> Table '{$tableName}' already exists. Skipping.";
                            }
                        } elseif (preg_match('/^ALTER TABLE `?(\w+)`? ADD COLUMN `?(\w+)`?/i', $statement, $matches)) {
                            $tableName = $matches[1];
                            $columnName = $matches[2];
                            if (!columnExists($pdo, $tableName, $columnName)) {
                                $pdo->exec($statement);
                                $logs[] = "<span style='color: #22c55e;'>SUCCESS:</span> Column '{$columnName}' added to table '{$tableName}'.";
                            } else {
                                $logs[] = "<span style='color: #f59e0b;'>INFO:</span> Column '{$columnName}' in table '{$tableName}' already exists. Skipping.";
                            }
                        } else {
                            $pdo->exec($statement);
                        }
                    } catch (PDOException $e) {
                        // Catch errors on a per-statement basis, log them, and continue.
                        // We ignore "duplicate entry" errors, which are expected on reruns.
                        if ($e->getCode() != 23000) { 
                             $errors[] = "Error on statement near '" . htmlspecialchars(substr($statement, 0, 50)) . "...' Reason: " . $e->getMessage();
                             $logs[] = "<span style='color: #ef4444;'>ERROR:</span> " . $errors[count($errors)-1];
                        }
                    }
                }
                 $logs[] = "--- Database Schema Update Finished ---";
            } else {
                $logs[] = "<span style='color: #f59e0b;'>INFO:</span> 'database.sql' not found. Skipping database schema update.";
            }

            // 2. Process Language File
            $logs[] = "--- Starting Language Settings Update ---";
            $lang_file = $languages_dir . $selected_lang_code . '.php';
            $lang_data = require $lang_file;
            $new_settings = $lang_data['site_settings'] ?? [];
            
            if (!empty($new_settings)) {
                $insert_stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
                $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM site_settings WHERE setting_key = ?");
                
                $new_keys_added = 0;
                foreach ($new_settings as $key => $value) {
                    $check_stmt->execute([$key]);
                    if ($check_stmt->fetchColumn() == 0) {
                        $insert_stmt->execute([$key, $value]);
                        $new_keys_added++;
                    }
                }

                if ($new_keys_added > 0) {
                    $logs[] = "<span style='color: #22c55e;'>SUCCESS:</span> Added $new_keys_added new language setting(s) from '{$selected_lang_code}.php'.";
                } else {
                    $logs[] = "<span style='color: #f59e0b;'>INFO:</span> All language settings from '{$selected_lang_code}.php' already exist. No new keys added.";
                }
            } else {
                 $logs[] = "<span style='color: #f59e0b;'>INFO:</span> No 'site_settings' found in '{$selected_lang_code}.php'.";
            }

            // 3. Force Footer Credits
            $logs[] = "--- Standardizing Footer Credits ---";
            $footer_credits_key = 'footer_credits';
            $footer_credits_value = 'Powered by myZedora CMS';
            $credit_stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $credit_stmt->execute([':key' => $footer_credits_key, ':value' => $footer_credits_value]);
            $logs[] = "<span style='color: #22c55e;'>SUCCESS:</span> Footer credits have been set to the default value.";

            $logs[] = "--- Language Settings Update Finished ---";

            // If no errors were logged during the entire process, mark as successful.
            if (empty($errors)) {
                $update_successful = true;
                file_put_contents($lock_file_path, 'Update completed on ' . date('Y-m-d H:i:s'));
            }

        } catch (Exception $e) {
            // This will now only catch critical, unexpected errors.
            $errors[] = "A critical error occurred: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>myZedora CMS Updater</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #111827; color: #e5e7eb; }
        .container { max-width: 896px; }
        .card { background-color: #1f2937; border: 1px solid #374151; }
        .btn-primary { background-color: #0ea5e9; color: white; font-weight: 600; padding: 12px 24px; border-radius: 0.5rem; transition: background-color 0.3s; cursor: pointer; }
        .btn-primary:hover { background-color: #0284c7; }
        .btn-primary:disabled { background-color: #374151; cursor: not-allowed; }
        .log-box { background-color: #111827; border: 1px solid #4b5563; max-height: 300px; overflow-y: auto; }
        h1.title-gradient {
            background: -webkit-linear-gradient(45deg, #38bdf8, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <div class="container mx-auto px-4 py-12">
        <div class="card rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-center mb-2 title-gradient">myZedora CMS Updater</h1>
            <p class="text-center text-gray-400 mb-8">This script will update your database schema and language settings.</p>

            <?php if ($update_successful): ?>
                <div class="bg-green-500/20 text-green-300 p-6 rounded-lg mb-6">
                    <h2 class="text-xl font-bold mb-2">Update Complete!</h2>
                    <p>Your system has been successfully updated.</p>
                    <p class="mt-4 font-semibold text-yellow-300">
                        For security, please DELETE the `/update/` directory on your server now.
                    </p>
                </div>
            <?php elseif (!empty($errors)): ?>
                <div class="bg-red-500/20 text-red-300 p-6 rounded-lg mb-6">
                    <h2 class="text-xl font-bold mb-2">Update Finished with Errors</h2>
                    <p>The script finished but encountered some issues. Please review the log below. You can safely re-run the updater.</p>
                </div>
            <?php endif; ?>

            <?php if (!empty($logs)): ?>
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-white mb-2">Update Log:</h3>
                    <div class="log-box rounded-lg p-4 space-y-2 text-sm font-mono">
                        <?php foreach ($logs as $log): ?>
                            <p><?php echo $log; ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($step === 1 && empty($errors) && !$update_successful): ?>
                <div class="text-center">
                    <form method="POST" action="index.php">
                        <?php if (!empty($available_languages)): ?>
                        <div class="mb-6">
                            <label for="language" class="block text-gray-300 mb-2 font-semibold">Select Language for New Settings</label>
                            <select name="language" id="language" class="w-full max-w-xs mx-auto bg-gray-700 border border-gray-600 rounded-lg py-2.5 px-4 text-white">
                                <?php foreach ($available_languages as $code => $name): ?>
                                <option value="<?php echo $code; ?>" <?php echo $code === 'en' ? 'selected' : ''; ?>><?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <button type="submit" name="run_update" class="btn-primary" <?php if (empty($available_languages)) echo 'disabled'; ?>>
                            <?php echo empty($available_languages) ? 'Language Files Missing' : 'Click Here to Update Now'; ?>
                        </button>
                         <?php if (empty($available_languages)): ?>
                            <p class="text-xs text-red-400 mt-2">The `/update/languages/` directory is missing or empty.</p>
                        <?php endif; ?>
                    </form>
                </div>
            <?php endif; ?>

        </div>
    </div>
</body>
</html>