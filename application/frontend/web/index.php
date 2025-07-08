<?php

try {
    /* NOTE: The composer autoloader will be one of the first things loaded by
     *       this required file.  */
    $config = require('../config/load-configs.php');
} catch (Sil\PhpEnv\EnvVarNotFoundException $e) {
    // Return error response code/message to HTTP request.
    header('Content-Type: application/json');
    http_response_code(500);
    $responseContent = json_encode([
        'name' => 'Internal Server Error',
        'message' => $e->getMessage(),
        'status' => 500,
    ]);
    fwrite(fopen('php://stderr', 'w'), $responseContent . PHP_EOL);
    exit($responseContent);
}

ini_set('display_errors', 1); error_reporting(E_ALL);

if (getenv('MYSQL_ATTR_SSL_CA')) {
    $caPath = '/data/console/runtime';
    $caFile = $caPath . '/ca.pem';
    $decoded = base64_decode(getenv('MYSQL_ATTR_SSL_CA'));
    if (file_put_contents($caFile, $decoded) === false) {
        $err = " perms: " . sprintf('%o', fileperms($caPath));
        $err .= " user: " . get_current_user();
        $err .= " whoami: " . exec('whoami');
        $err .= " owner: " . json_encode(posix_getpwuid(fileowner($caPath)));
        die('Failed to write database SSL certificate file: ' . $caFile . $err);
    }
    chmod($caFile, 0600);
}

$application = new yii\web\Application($config);
$application->run();
