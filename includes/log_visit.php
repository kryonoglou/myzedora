<?php

function is_bot($user_agent) {
    if (empty($user_agent)) return false;
    $bots = ['Googlebot', 'Bingbot', 'Slurp', 'DuckDuckBot', 'Baiduspider', 'YandexBot', 'Sogou', 'Exabot', 'facebot', 'ia_archiver'];
    foreach ($bots as $bot) {
        if (stripos($user_agent, $bot) !== false) {
            return true;
        }
    }
    return false;
}

if (!isset($_COOKIE['visitor_id'])) {
    $visitor_id = uniqid('visitor_', true);
    setcookie('visitor_id', $visitor_id, time() + (86400 * 365), "/");
} else {
    $visitor_id = $_COOKIE['visitor_id'];
}

$user_id = $_SESSION['user_id'] ?? null;
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$is_bot_visit = is_bot($user_agent);

$page_type = null;
$item_id = null;
$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$slug = $_GET['slug'] ?? null;

if ($slug && (strpos($request_path, '/post/') !== false || strpos($request_path, 'post/index.php') !== false)) {
    $page_type = 'post';
} elseif ($slug && (strpos($request_path, '/project/') !== false || strpos($request_path, 'project/index.php') !== false)) {
    $page_type = 'project';
} elseif ($request_path === '/' || basename($request_path) === 'index.php') {
    $page_type = 'home';
}

if (($page_type === 'post' || $page_type === 'project') && $slug) {
    $table = ($page_type === 'post') ? 'posts' : 'projects';
    try {
        $stmt = $pdo->prepare("SELECT id FROM $table WHERE slug = ?");
        $stmt->execute([$slug]);
        $fetched_id = $stmt->fetchColumn();
        if ($fetched_id) {
            $item_id = $fetched_id;
        } else {
            $page_type = null; 
        }
    } catch (PDOException $e) {
        $page_type = null;
    }
}

if ($page_type) {
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO statistics (visitor_id, user_id, page_type, item_id, user_agent, is_bot) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$visitor_id, $user_id, $page_type, $item_id, $user_agent, $is_bot_visit]);
    } catch (PDOException $e) {
    }
}
?>