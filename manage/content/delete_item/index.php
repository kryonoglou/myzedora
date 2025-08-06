<?php
require_once dirname(__DIR__, 3) . '/includes/map.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: " . HOME_URL);
    exit();
}

$item_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$item_type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);

if (!$item_id || !$item_type) {
    header("Location: " . MANAGE_CONTENT_URL);
    exit();
}

$table = '';
$redirect_url = MANAGE_CONTENT_URL;

switch ($item_type) {
    case 'post':
        $table = 'posts';
        break;
    case 'project':
        $stmt_get_image = $pdo->prepare("SELECT image_url FROM projects WHERE id = ?");
        $stmt_get_image->execute([$item_id]);
        $image_url = $stmt_get_image->fetchColumn();

        if ($image_url && strpos($image_url, '/img/projects/') !== false) {
            $filename_to_delete = basename($image_url);
            $file_path_to_delete = dirname(__DIR__, 3) . '/img/projects/' . $filename_to_delete;
            if (file_exists($file_path_to_delete)) {
                @unlink($file_path_to_delete);
            }
        }
        $table = 'projects';
        break;
    default:
        header("Location: " . MANAGE_CONTENT_URL);
        exit();
}

if ($table) {
    try {
        $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
        $stmt->execute([$item_id]);
    } catch (PDOException $e) {
    }
}

header("Location: " . $redirect_url);
exit();