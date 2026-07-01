<?php
/**
 * 入口文件
 * 关闭Session，无状态高性能设计
 */

// 关闭Session以最大化性能
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// 禁用session auto start
ini_set('session.auto_start', '0');

// 定义基础路径
define('BASEPATH', dirname(__DIR__));
define('PUBLIC_PATH', __DIR__);

// 自动加载
require_once dirname(__DIR__) . '/vendor/autoload.php';

// 加载路由定义
$routesFile = dirname(__DIR__) . '/routes/api.php';

if (file_exists($routesFile)) {
    require $routesFile;
}

// 加载配置
$configFile = dirname(__DIR__) . '/config/app.php';

if (file_exists($configFile)) {
    $appConfig = require $configFile;
} else {
    $appConfig = [];
}

// 创建应用实例
use QuickAPI\Core\App;

// 获取应用实例并配置
$app = App::getInstance();

// 应用配置
$app->config($appConfig);

// 如果有中间件配置
if (isset($appConfig['middleware'])) {
    $app->middleware($appConfig['middleware']);
}

// 运行应用
$app->run();
