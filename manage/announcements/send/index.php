<?php
require_once dirname(__DIR__, 3) . '/includes/map.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: " . HOME_URL);
    exit();
}

$announcement_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$announcement_id) {
    header("Location: " . SEND_ANNOUNCEMENTS_URL);
    exit();
}

try {

    $stmt_ann = $pdo->prepare("SELECT subject, message FROM announcements WHERE id = ?");
    $stmt_ann->execute([$announcement_id]);
    $announcement = $stmt_ann->fetch();

    if (!$announcement) {
        header("Location: " . SEND_ANNOUNCEMENTS_URL);
        exit();
    }

    $stmt_users = $pdo->query("SELECT email, full_name FROM users WHERE allow_announcements = 1 AND is_active = 1");
    $subscribers = $stmt_users->fetchAll();

    if (empty($subscribers)) {
        $_SESSION['message'] = htmlspecialchars($settings_data['announcement_error_no_subscribers']);
        $_SESSION['message_type'] = 'error';
        header("Location: " . SEND_ANNOUNCEMENTS_URL);
        exit();
    }
    
    foreach ($subscribers as $subscriber) {
        $body_replacements = ['{{name}}' => htmlspecialchars($subscriber['full_name'])];
        $personalized_body = str_replace(array_keys($body_replacements), array_values($body_replacements), $announcement['message']);
        
        send_email($subscriber['email'], $announcement['subject'], $personalized_body, $settings_data);
    }
    
    $stmt_update = $pdo->prepare("UPDATE announcements SET last_sent_at = NOW() WHERE id = ?");
    $stmt_update->execute([$announcement_id]);

    $_SESSION['message'] = str_replace('{{count}}', count($subscribers), htmlspecialchars($settings_data['announcement_sent_success']));
    $_SESSION['message_type'] = 'success';

} catch (PDOException $e) {
    $_SESSION['message'] = htmlspecialchars($settings_data['announcement_sent_fail']);
    $_SESSION['message_type'] = 'error';
}

header("Location: " . SEND_ANNOUNCEMENTS_URL);
exit();
?>