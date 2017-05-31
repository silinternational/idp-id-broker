<?php

try {
    /* NOTE: The composer autoloader will be one of the first things loaded by
     *       this required file.  */
    $config = require('../config/load-configs.php');
} catch (Sil\PhpEnv\EnvVarNotFoundException $e) {
    
    // Log to syslog (Logentries).
    openlog('id-broker', LOG_NDELAY | LOG_PERROR, LOG_USER);
    syslog(LOG_CRIT, $e->getMessage());
    closelog();
    
    // Return error response code/message to HTTP request.
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
