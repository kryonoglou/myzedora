<?php
require_once dirname(__DIR__) . '/includes/map.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: " . HOME_URL);
    exit();
}

$style_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$style_id) {
    header("Location: " . STYLES_URL);
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM custom_styles WHERE id = ?");
    $stmt->execute([$style_id]);
} catch (PDOException $e) {
}

header("Location: " . STYLES_URL);
exit();
?>