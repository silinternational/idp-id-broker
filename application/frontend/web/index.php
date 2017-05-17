<?php

try {
    $config = require('../config/load-configs.php');
} catch (EnvVarNotFoundException $e) {
    header('Content-Type: application/json');
    http_response_code(500);

    $responseContent = json_encode([
        'name' => 'Internal Server Error',
        'message' => $e->getMessage(),
        'status' => 500,
    ], JSON_PRETTY_PRINT);

    exit($responseContent);
}

$application = new yii\web\Application($config);
$application->run();
