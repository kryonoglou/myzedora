<?php
require_once dirname(__DIR__) . '/includes/map.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: " . HOME_URL);
    exit();
}

$item_type = $_GET['type'] ?? '';
$item_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$item_id || !in_array($item_type, ['post', 'project'])) {
    header("Location: " . MANAGE_URL);
    exit();
}

try {
    $pdo->beginTransaction();

    if ($item_type === 'post') {
        $stmt_comments = $pdo->prepare("DELETE FROM comments WHERE post_id = ?");
        $stmt_comments->execute([$item_id]);
    }
    
    $table_name = ($item_type === 'post') ? 'posts' : 'projects';
    $stmt_item = $pdo->prepare("DELETE FROM $table_name WHERE id = ?");
    $stmt_item->execute([$item_id]);

    $pdo->commit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

header("Location: " . MANAGE_URL);
exit();
?>