<?php
/**
 * 应用入口文件
 * 
 * 熊猫API框架 (PandaAPI) MVC 应用入口
 * 
 * 请求流程：
 * 1. 加载自动加载
 * 2. 加载公共函数
 * 3. 加载配置
 * 4. 初始化数据库和缓存
 * 5. 加载路由
 * 6. 运行应用
 */

// 关闭 Session（无状态设计）
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}
ini_set('session.auto_start', '0');

// 加载 Composer 自动加载
require_once __DIR__ . '/../vendor/autoload.php';

// 加载公共函数
require_once __DIR__ . '/common/functions.php';
require_once __DIR__ . '/common/middleware.php';

// 加载应用配置
$configFile = __DIR__ . '/../config/app.php';
if (file_exists($configFile)) {
    $config = require $configFile;
} else {
    $config = [
        'env'      => 'development',
        'debug'    => true,
        'timezone' => 'Asia/Shanghai',
    ];
}

// 设置时区
date_default_timezone_set($config['timezone'] ?? 'Asia/Shanghai');

// 初始化数据库
use PandaAPI\Database\PandaDB;
PandaDB::config([
    'driver'   => $config['database']['driver']   ?? 'mysql',
    'host'     => $config['database']['host']     ?? '127.0.0.1',
    'port'     => $config['database']['port']     ?? 3306,
    'database' => $config['database']['database'] ?? 'pandaapi',
    'username' => $config['database']['username'] ?? 'root',
    'password' => $config['database']['password'] ?? '',
    'charset'  => $config['database']['charset']  ?? 'utf8mb4',
    'prefix'   => $config['database']['prefix']   ?? '',
]);

// 初始化缓存
use PandaAPI\Cache\Cache;
Cache::config([
    'driver'      => $config['cache']['driver']      ?? 'file',
    'file'        => $config['cache']['file']        ?? ['path' => __DIR__ . '/../runtime/cache'],
    'redis'       => $config['cache']['redis']       ?? [],
    'default_ttl' => $config['cache']['default_ttl'] ?? 3600,
]);

// 设置慢查询阈值
PandaDB::setSlowThreshold($config['slow_query_threshold'] ?? 100);

// 加载路由
require_once __DIR__ . '/router/routes.php';

// 运行应用
use PandaAPI\Core\App;
$app = new App();
$app->config($config);
$app->run();
