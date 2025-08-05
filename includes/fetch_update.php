<?php

header('Content-Type: application/json');

require_once __DIR__ . '/map.php';

$update_url = 'https://www.myzedora.com/get/status/version.json';

$response = [
    'latestVersion' => null,
    'downloadUrl' => '#',
    'updateAvailable' => false,
    'error' => null
];

try {
    $context = stream_context_create(['http' => ['timeout' => 5]]);
    $remote_data_json = @file_get_contents($update_url, false, $context);

    if ($remote_data_json === false) {
        throw new Exception("Could not fetch update data from the server.");
    }

    $remote_data = json_decode($remote_data_json, true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($remote_data['version']) || !isset($remote_data['download_url'])) {
        throw new Exception("Invalid data format received from the update server.");
    }

    $response['latestVersion'] = htmlspecialchars($remote_data['version']);
    $response['downloadUrl'] = htmlspecialchars($remote_data['download_url']);

    if (version_compare($response['latestVersion'], MYZEDORA_VERSION, '>')) {
        $response['updateAvailable'] = true;
    }

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
exit();