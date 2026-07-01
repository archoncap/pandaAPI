<?php
/**
 * QuickAPI Demo - 配置文件
 * 
 * @author QuickAPI Team
 */

return [
    // 环境配置
    'env' => 'development',
    'debug' => true,
    'timezone' => 'Asia/Shanghai',
    
    // 数据库配置
    'database' => [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'quickapi_demo',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'prefix' => 'demo_',
        
        // 连接池配置
        'pool' => [
            'max' => 10,           // 最大连接数
            'min' => 2,            // 最小连接数
            'idle_timeout' => 60, // 空闲超时(秒)
            'connect_timeout' => 5, // 连接超时(秒)
        ],
    ],
    
    // 缓存配置
    'cache' => [
        // 驱动类型: file / redis / memcache / openresty
        'driver' => 'file',
        
        // 文件缓存配置
        'file' => [
            'path' => __DIR__ . '/../runtime/cache',
        ],
        
        // Redis缓存配置
        'redis' => [
            'host' => '127.0.0.1',
            'port' => 6379,
            'password' => null,
            'database' => 0,
            'prefix' => 'quickapi:',
        ],
        
        // Memcache缓存配置
        'memcache' => [
            'host' => '127.0.0.1',
            'port' => 11211,
            'persistent' => false,
        ],
        
        // OpenResty缓存配置
        'openresty' => [
            'host' => '127.0.0.1',
            'port' => 8099,
        ],
        
        // 默认TTL
        'default_ttl' => 3600,
    ],
    
    // 全局中间件
    'middleware' => [
        'Cors',   // 跨域
        'Log',    // 日志
    ],
    
    // 限流配置
    'throttle' => [
        'max_attempts' => 60,      // 最大尝试次数
        'decay_minutes' => 1,      // 衰减时间(分钟)
    ],
    
    // CORS配置
    'cors' => [
        'origin' => '*',
        'methods' => 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
        'headers' => 'Content-Type, Authorization, X-Requested-With',
    ],
    
    // 慢查询阈值(毫秒)
    'slow_query_threshold' => 100,
];
