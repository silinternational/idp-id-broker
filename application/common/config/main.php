<?php

use Sil\PhpEnv\Env;

$mysqlHost     = Env::get('MYSQL_HOST');
$mysqlDatabase = Env::get('MYSQL_DATABASE');
$mysqlUser     = Env::get('MYSQL_USER');
$mysqlPassword = Env::get('MYSQL_PASSWORD');

return [
    'id' => 'app-common',
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => sprintf('mysql:host=%s;dbname=%s', $mysqlHost, $mysqlDatabase),
            'username' => $mysqlUser,
            'password' => $mysqlPassword,
            'charset' => 'utf8',
            'emulatePrepare' => false, // TODO: why this setting?
            'tablePrefix' => '', // TODO: what's the default here?
        ],
    ],
];
