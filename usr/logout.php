<?php
require_once dirname(__DIR__) . '/includes/map.php';

$_SESSION = [];

session_destroy();

header("Location: " . HOME_URL);
exit();
?>