<?php
require_once dirname(__DIR__, 2) . '/includes/map.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: " . HOME_URL);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['image_id'])) {
    $image_id = filter_var($_POST['image_id'], FILTER_VALIDATE_INT);

    if ($image_id) {
        $stmt = $pdo->prepare("SELECT filename FROM gallery WHERE id = ?");
        $stmt->execute([$image_id]);
        $filename = $stmt->fetchColumn();

        if ($filename) {
            $file_path = PROJECT_ROOT . '/img/gallery/' . $filename;

            if (file_exists($file_path)) {
                @unlink($file_path);
            }

            $delete_stmt = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
            $delete_stmt->execute([$image_id]);
            
            $_SESSION['success_message'] = htmlspecialchars($settings_data['gallery_delete_success']);
        } else {
            $_SESSION['errors'] = [htmlspecialchars($settings_data['gallery_delete_fail'])];
        }
    } else {
        $_SESSION['errors'] = [htmlspecialchars($settings_data['gallery_delete_fail'])];
    }
}

header("Location: " . GALLERY_URL);
exit();