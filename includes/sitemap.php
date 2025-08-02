<?php

require_once __DIR__ . '/map.php';

header('Content-Type: application/xml; charset=utf-8');

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

function create_url_entry($url) {
    echo '  <url>' . PHP_EOL;
    echo '    <loc>' . htmlspecialchars($url) . '</loc>' . PHP_EOL;
    echo '  </url>' . PHP_EOL;
}

create_url_entry(HOME_URL);
create_url_entry(LOGIN_URL);
create_url_entry(REGISTER_URL);
create_url_entry(POSTS_PAGE_URL);

$posts_stmt = $pdo->query("SELECT slug FROM posts WHERE is_published = 1");
while ($post = $posts_stmt->fetch(PDO::FETCH_ASSOC)) {
    create_url_entry(POST_URL_BASE . urlencode($post['slug']));
}

$projects_stmt = $pdo->query("SELECT slug FROM projects WHERE is_published = 1");
while ($project = $projects_stmt->fetch(PDO::FETCH_ASSOC)) {
    create_url_entry(PROJECT_URL_BASE . urlencode($project['slug']));
}

$users_stmt = $pdo->query("SELECT username FROM users");
while ($user = $users_stmt->fetch(PDO::FETCH_ASSOC)) {
    create_url_entry(PROFILE_URL_BASE . urlencode($user['username']));
}

echo '</urlset>';