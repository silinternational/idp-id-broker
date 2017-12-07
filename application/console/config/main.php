<?php


use Sil\PhpEnv\Env;



return [
    'id' => 'app-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['gii'],
    'controllerNamespace' => 'console\controllers',
    'modules' => [
        'gii' => 'yii\gii\Module',
    ],
];
