<?php
require_once dirname(__DIR__) . '/includes/map.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: " . HOME_URL);
    exit();
}

$user_id_to_delete = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$user_id_to_delete || $user_id_to_delete === $_SESSION['user_id']) {
    header("Location: " . MEMBERS_LIST_URL);
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id_to_delete]);
} catch (PDOException $e) {

}

header("Location: " . MEMBERS_LIST_URL);
exit();
?>