<?php

use Sil\PhpEnv\Env;

$mysqlHost     = getRequiredEnv('MYSQL_HOST');
$mysqlDatabase = getRequiredEnv('MYSQL_DATABASE');
$mysqlUser     = getRequiredEnv('MYSQL_USER');
$mysqlPassword = getRequiredEnv('MYSQL_PASSWORD');

function getRequiredEnv($name)
{
    $value = Env::get($name);

    if (empty($value)) {
        die("$name missing from environment.");
    }

    return $value;
}

return [
    'id' => 'app-common',
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => sprintf('mysql:host=%s;dbname=%s', $mysqlHost, $mysqlDatabase),
            'username' => $mysqlUser,
            'password' => $mysqlPassword,
            'charset' => 'utf8',
        ],
    ],
];
