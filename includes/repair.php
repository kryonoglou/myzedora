<?php

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__));
}

$required_directories = [
    PROJECT_ROOT . '/ext',
    PROJECT_ROOT . '/img/projects',
    PROJECT_ROOT . '/img/gallery',
    PROJECT_ROOT . '/img/users',
];

foreach ($required_directories as $dir) {
    if (!file_exists($dir)) {
        @mkdir($dir, 0755, true);
    }
}

?>