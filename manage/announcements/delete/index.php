<?php
require_once dirname(__DIR__, 3) . '/includes/map.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: " . HOME_URL);
    exit();
}

$announcement_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($announcement_id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->execute([$announcement_id]);
        $_SESSION['message'] = $settings_data['announcement_deleted_success'];
        $_SESSION['message_type'] = 'success';
    } catch (PDOException $e) {
        $_SESSION['message'] = "Error deleting announcement.";
        $_SESSION['message_type'] = 'error';
    }
}

header("Location: " . SEND_ANNOUNCEMENTS_URL);
exit();
?>