<?php


use Sil\PhpEnv\Env;

/* For pushing data to Google Analytics */
$gaTrackingId = Env::get('GA_TRACKING_ID', null); // 'UA-12345678-12'
$gaClientId = Env::get('GA_CLIENT_ID', null); // 'IDP_ID_BROKER_LOCALHOST'


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
