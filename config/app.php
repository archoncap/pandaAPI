<?php
/**
 * 应用配置文件
 */
return [
    // 环境：development / production / testing
    'env'      => 'development',
    'debug'    => true,
    'timezone' => 'Asia/Shanghai',

    // 数据库配置
    'database' => [
        'driver'   => 'mysql',
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'database' => 'pandaapi',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
        'prefix'   => '',
    ],

    // 缓存配置
    'cache' => [
        'driver' => 'file',
        'file' => [
            'path' => dirname(__DIR__) . '/runtime/cache',
        ],
        'redis' => [
            'host'     => '127.0.0.1',
            'port'     => 6379,
            'password' => null,
            'database' => 0,
            'prefix'   => 'pandaapi:',
        ],
        'default_ttl' => 3600,
    ],

    // CORS 配置
    'cors' => [
        'origin'  => '*',
        'methods' => 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
        'headers' => 'Content-Type, Authorization, X-Requested-With',
    ],

    // 慢查询阈值（毫秒）
    'slow_query_threshold' => 100,
];
