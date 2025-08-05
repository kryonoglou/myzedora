<?php

require_once __DIR__ . '/map.php';

header('Content-Type: text/plain; charset=utf-8');

$base_path = parse_url($_ENV['APP_URL'] ?? '', PHP_URL_PATH);
if ($base_path === null) {
    $base_path = '/';
}
$base_path = rtrim($base_path, '/') . '/';

echo "User-agent: *" . PHP_EOL;
echo PHP_EOL;

echo "# Disallow admin and management areas" . PHP_EOL;
echo "Disallow: " . $base_path . "access/" . PHP_EOL;
echo "Disallow: " . $base_path . "admin/" . PHP_EOL;
echo "Disallow: " . $base_path . "manage/" . PHP_EOL;
echo "Disallow: " . $base_path . "statistics/" . PHP_EOL;
echo "Disallow: " . $base_path . "includes/" . PHP_EOL;
echo PHP_EOL;

echo "# Disallow specific sensitive files" . PHP_EOL;
echo "Disallow: " . $base_path . "install.php" . PHP_EOL;
echo "Disallow: " . $base_path . "edit_profile.php" . PHP_EOL;
echo "Disallow: " . $base_path . "manage.php" . PHP_EOL;
echo PHP_EOL;

echo "# Sitemap" . PHP_EOL;
echo "Sitemap: " . SITEMAP_URL . PHP_EOL;
?>