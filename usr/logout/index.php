<?php
require_once dirname(__DIR__, 2) . '/includes/map.php';

$_SESSION = [];

session_destroy();

header("Location: " . HOME_URL);
exit();
?>