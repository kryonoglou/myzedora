<?php

ini_set('display_errors', 0);

error_reporting(E_ALL);

define('MYZEDORA_ERROR_LOG_DIR', dirname(__DIR__) . '/includes/errors/');

register_shutdown_function(function() {
    $error = error_get_last();

    $fatal_error_types = [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_PARSE];

    if ($error !== null && in_array($error['type'], $fatal_error_types)) {
        
        if (!file_exists(MYZEDORA_ERROR_LOG_DIR)) {
            @mkdir(MYZEDORA_ERROR_LOG_DIR, 0755, true);
        }

        $error_file_basename = basename($error['file'], '.php');
        
        $log_file_path = MYZEDORA_ERROR_LOG_DIR . $error_file_basename . '.log';

        $timestamp = date("Y-m-d H:i:s");
        $log_entry  = "[$timestamp] FATAL ERROR" . PHP_EOL;
        $log_entry .= "=====================================" . PHP_EOL;
        $log_entry .= "Type:      " . $error['type'] . PHP_EOL;
        $log_entry .= "Message:   " . $error['message'] . PHP_EOL;
        $log_entry .= "File:      " . $error['file'] . PHP_EOL;
        $log_entry .= "Line:      " . $error['line'] . PHP_EOL;
        $log_entry .= "--------------------------------------------------" . PHP_EOL . PHP_EOL;

        @file_put_contents($log_file_path, $log_entry, FILE_APPEND);
    }
});