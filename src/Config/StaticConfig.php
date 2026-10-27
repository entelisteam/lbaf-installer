<?php
declare(strict_types=1);

namespace Skeleton\Config;

use EntelisTeam\Lbaf\MySql\MySqlConfig;

class StaticConfig
{
    public static function getMySqlConfig(): MySqlConfig
    {
        //MySqlConfig сам разбирает host вида 'hostname:port' и префикс 'p:'
        $config = new MySqlConfig(
            host: getenv('APP_DB_HOST') ?: 'localhost',
            user: getenv('APP_DB_USER') ?: 'root',
            password: getenv('APP_DB_PASSWORD') ?: '',
            database: getenv('APP_DB_NAME') ?: 'skeleton',
        );

        //setSocketConnection сам ставит host=localhost, иначе mysqli уйдет по tcp и сокет проигнорит
        $socket = getenv('APP_DB_SOCKET');
        if ($socket !== false && $socket !== '') {
            $config->setSocketConnection($socket);
        }

        return $config;
    }
}
