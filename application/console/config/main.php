<?php


use Sil\PhpEnv\Env;

/* For pushing data to Google Analytics */
$gaTrackingId = Env::get('GA_TRACKING_ID');
$gaClientId = Env::get('GA_CLIENT_ID');


return [
    'id' => 'app-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['gii'],
    'controllerNamespace' => 'console\controllers',
    'modules' => [
        'gii' => 'yii\gii\Module',
    ],
    'params' => [
        'gaTrackingId'              => $gaTrackingId,
        'gaClientId'                => $gaClientId,
    ],
];
