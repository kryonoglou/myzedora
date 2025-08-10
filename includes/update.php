<?php
session_start();
header('Content-Type: application/json');

set_time_limit(300);
ini_set('memory_limit', '256M');

require_once __DIR__ . '/map.php';

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    $response['message'] = 'Authentication failed. You must be an administrator to perform this action.';
    echo json_encode($response);
    exit();
}

try {
    $version_manifest_url = 'https://get.myzedora.com/version.json';
    $context = stream_context_create(['http' => ['timeout' => 30]]);
    $manifest_json = @file_get_contents($version_manifest_url, false, $context);

    if ($manifest_json === false) {
        throw new Exception("Could not fetch the version manifest from the update server. Please check your connection or try again later.");
    }

    $manifest_data = json_decode($manifest_json, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($manifest_data['update_url'], $manifest_data['download_url'])) {
        throw new Exception("The version manifest file is invalid or corrupted.");
    }

    $update_url_base = rtrim($manifest_data['update_url'], '/') . '/lang';

    $site_lang = $settings_data['site_language'] ?? 'en';

    $database_update_url = $update_url_base . '/' . $site_lang . '.json';
    $db_update_json = @file_get_contents($database_update_url, false, $context);

    if ($db_update_json === false) {
        throw new Exception("Could not fetch the database update file for language '{$site_lang}' from the server.");
    }

    $db_updates = json_decode($db_update_json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("The database update file is invalid or corrupted.");
    }

    if (!empty($db_updates)) {
        $pdo->beginTransaction();

        $override_stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $insert_ignore_stmt = $pdo->prepare("INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES (:key, :value)");

        foreach ($db_updates as $key => $value) {
            if ($key === 'footer_credits') {
                $override_stmt->execute([':key' => $key, ':value' => $value]);
            } else {
                $insert_ignore_stmt->execute([':key' => $key, ':value' => $value]);
            }
        }
        $pdo->commit();
    }

    $file_download_url = $manifest_data['download_url'];
    $zip_content = @file_get_contents($file_download_url, false, $context);
    if ($zip_content === false) {
        throw new Exception("Could not download the update package (files.zip).");
    }

    $zip_path = PROJECT_ROOT . '/update.zip';
    if (file_put_contents($zip_path, $zip_content) === false) {
        throw new Exception("Could not save the update package. Please check file permissions for the website's root directory.");
    }

    $zip = new ZipArchive;
    $res = $zip->open($zip_path);
    if ($res === TRUE) {
        $zip->extractTo(PROJECT_ROOT . '/');
        $zip->close();
        unlink($zip_path);

        $response['status'] = 'success';
        $response['message'] = 'Update complete! Your system has been successfully updated to the latest version.';
    } else {
        unlink($zip_path);
        throw new Exception("Failed to open or extract the update package. The file may be corrupt.");
    }

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (file_exists($zip_path ?? '')) {
        unlink($zip_path);
    }
    $response['status'] = 'error';
    $response['message'] = 'Update failed: ' . $e->getMessage();
}

echo json_encode($response);
exit();
?>
