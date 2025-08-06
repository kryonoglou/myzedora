<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/vendor/autoload.php';

function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if (preg_match('/^"(.*)"$/', $value, $matches)) {
            $value = $matches[1];
        }

        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

$dotenv_path = dirname(__DIR__) . '/.env';
if (!file_exists($dotenv_path)) {
    die("Error: Could not find the .env file. Please ensure it exists in the project root or run install.php.");
}
loadEnv($dotenv_path);

if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

$db_host = $_ENV['DB_HOST'] ?? null;
$db_port = $_ENV['DB_PORT'] ?? '3306';
$db_name = $_ENV['DB_DATABASE'] ?? null;
$db_user = $_ENV['DB_USERNAME'] ?? null;
$db_pass = $_ENV['DB_PASSWORD'] ?? null;
$db_charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

if (!$db_host || !$db_name || !$db_user) {
    die("Database configuration variables are missing. Please check your .env file.");
}

$dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=$db_charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       
    PDO::ATTR_EMULATE_PREPARES   => false,                 
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (\PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

?>