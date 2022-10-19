<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

return [
    // 默认数据库
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'url' => getenv('DATABASE_URL'),
            'host' => getenv('DB_HOST','127.0.0.1'),
            'port' => getenv('DB_PORT', '3306'),
            'database' => getenv('DB_DATABASE', 'forge'),
            'username' => getenv('DB_USERNAME', 'forge'),
            'password' => getenv('DB_PASSWORD', ''),
            'unix_socket' => getenv('DB_SOCKET', ''),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ],
        'mongodb' => [
            'driver' => 'mongodb',
            'dsn' => getenv('MDB_DSN'),
            'host' => getenv('MDB_HOST'),
            'port' => getenv('MDB_PORT'),
            'database' => getenv('MDB_DATABASE'),
            'username' => getenv('MDB_USERNAME'),
            'password' => getenv('MDB_PASSWORD'),
            'options' => [
                'database' => getenv('DB_AUTHENTICATION_DATABASE', 'admin'), // required with Mongo 3+
            ],
        ],
    ]
];
