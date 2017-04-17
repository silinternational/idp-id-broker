<?php

use common\ldap\Ldap;
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
        'ldap' => [
            'class' => Ldap::class,
            'acct_suffix' => Env::get('LDAP_ACCT_SUFFIX'),
            'domain_controllers' => explode('|', Env::get('LDAP_DOMAIN_CONTROLLERS')),
            'base_dn' => Env::get('LDAP_BASE_DN'),
            'admin_username' => Env::get('LDAP_ADMIN_USERNAME'),
            'admin_password' => Env::get('LDAP_ADMIN_PASSWORD'),
            'use_ssl' => Env::get('LDAP_USE_SSL', true),
            'use_tls' => Env::get('LDAP_USE_TLS', true),
            'timeout' => Env::get('LDAP_TIMEOUT', 5),
        ],
    ],
    'params' => [
        'migratePasswordsFromLdap' => Env::get('MIGRATE_PW_FROM_LDAP', false),
    ],
];
