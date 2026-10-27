<?php
declare(strict_types=1);

use Skeleton\Config\StaticConfig;

//тот же MySqlConfig, что и у приложения — phinx и рантайм гарантированно ходят
//в одну базу с одной кодировкой
$db = StaticConfig::getMySqlConfig();

$environment = [
    'adapter' => 'mysql',
    'host' => $db->host,
    'name' => $db->database,
    'user' => $db->user,
    'pass' => $db->password,
    'port' => $db->port,
    'charset' => $db->encoding,
];

//pdo_mysql игнорирует host, когда в dsn есть unix_socket
if ($db->socket !== null && $db->socket !== '') {
    $environment['unix_socket'] = $db->socket;
}

return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'app',

        'app' => $environment,
    ],
];
