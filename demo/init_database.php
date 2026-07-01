<?php
/**
 * QuickAPI Demo - 数据库初始化脚本
 * 
 * 用于初始化演示项目的数据库表结构和示例数据
 * 运行此脚本前请确保数据库已创建
 * 
 * 使用方法：
 * php init_database.php
 * 
 * @author QuickAPI Team
 */

require_once __DIR__ . '/../vendor/autoload.php';

use QuickAPI\Database\DB;
use QuickAPI\Cache\Cache;

echo "========================================\n";
echo "QuickAPI Demo 数据库初始化\n";
echo "========================================\n\n";

// 配置数据库
DB::config([
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => 'quickapi_demo',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'prefix' => 'demo_',
]);

echo "[1/6] 正在创建用户表...\n";

try {
    // 创建用户表
    DB::execute("
        CREATE TABLE IF NOT EXISTS demo_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            status TINYINT DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表'
    ");
    echo "✓ 用户表创建成功\n";
} catch (Exception $e) {
    echo "✗ 用户表创建失败: " . $e->getMessage() . "\n";
    exit(1);
}

echo "[2/6] 正在创建文章表...\n";

try {
    // 创建文章表
    DB::execute("
        CREATE TABLE IF NOT EXISTS demo_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            content TEXT,
            views INT DEFAULT 0,
            status TINYINT DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at),
            FOREIGN KEY (user_id) REFERENCES demo_users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章表'
    ");
    echo "✓ 文章表创建成功\n";
} catch (Exception $e) {
    echo "✗ 文章表创建失败: " . $e->getMessage() . "\n";
    exit(1);
}

echo "[3/6] 正在创建分类表...\n";

try {
    // 创建分类表
    DB::execute("
        CREATE TABLE IF NOT EXISTS demo_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            slug VARCHAR(50) NOT NULL UNIQUE,
            parent_id INT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_slug (slug),
            INDEX idx_parent_id (parent_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='分类表'
    ");
    echo "✓ 分类表创建成功\n";
} catch (Exception $e) {
    echo "✗ 分类表创建失败: " . $e->getMessage() . "\n";
    exit(1);
}

echo "[4/6] 正在插入示例数据...\n";

try {
    // 插入示例用户
    $users = [
        ['name' => '张三', 'email' => 'zhangsan@example.com', 'password' => password_hash('123456', PASSWORD_DEFAULT)],
        ['name' => '李四', 'email' => 'lisi@example.com', 'password' => password_hash('123456', PASSWORD_DEFAULT)],
        ['name' => '王五', 'email' => 'wangwu@example.com', 'password' => password_hash('123456', PASSWORD_DEFAULT)],
        ['name' => '赵六', 'email' => 'zhaoliu@example.com', 'password' => password_hash('123456', PASSWORD_DEFAULT)],
        ['name' => '孙七', 'email' => 'sunqi@example.com', 'password' => password_hash('123456', PASSWORD_DEFAULT)],
    ];
    
    $userIds = [];
    foreach ($users as $user) {
        $user['created_at'] = date('Y-m-d H:i:s');
        $user['status'] = 1;
        $userIds[] = DB::table('users')->insert($user);
    }
    echo "✓ 插入 " . count($userIds) . " 个用户\n";
    
    // 插入示例分类
    $categories = [
        ['name' => '技术', 'slug' => 'tech'],
        ['name' => '生活', 'slug' => 'life'],
        ['name' => '旅行', 'slug' => 'travel'],
        ['name' => '美食', 'slug' => 'food'],
        ['name' => '读书', 'slug' => 'reading'],
    ];
    
    foreach ($categories as $cat) {
        $cat['created_at'] = date('Y-m-d H:i:s');
        DB::table('categories')->insert($cat);
    }
    echo "✓ 插入 " . count($categories) . " 个分类\n";
    
    // 插入示例文章
    $articles = [
        ['title' => 'PHP 7.4 新特性详解', 'content' => 'PHP 7.4 引入了许多新特性，包括类型属性、箭头函数、预加载等...', 'views' => 1250],
        ['title' => 'MySQL 性能优化技巧', 'content' => '本文介绍如何优化 MySQL 查询性能，包括索引优化、查询优化...', 'views' => 890],
        ['title' => 'Redis 缓存实战', 'content' => 'Redis 是一个高性能的键值存储系统，本文介绍如何在项目中使用 Redis...', 'views' => 1560],
        ['title' => 'RESTful API 设计原则', 'content' => 'RESTful API 是一种常见的 Web API 设计风格，本文介绍其设计原则...', 'views' => 2100],
        ['title' => 'Docker 容器化部署', 'content' => 'Docker 是一个开源的容器化平台，本文介绍如何使用 Docker 部署应用...', 'views' => 1780],
        ['title' => 'Vue.js 3.0 入门指南', 'content' => 'Vue.js 3.0 是一个渐进式 JavaScript 框架，本文介绍其基本用法...', 'views' => 2340],
        ['title' => 'Linux 系统管理基础', 'content' => 'Linux 是一个开源的操作系统，本文介绍基本的系统管理命令...', 'views' => 980],
        ['title' => 'Git 版本控制教程', 'content' => 'Git 是一个分布式版本控制系统，本文介绍 Git 的基本用法...', 'views' => 3200],
        ['title' => 'JavaScript ES6 新特性', 'content' => 'ES6 是 JavaScript 的一个重要版本，引入了许多新语法...', 'views' => 1890],
        ['title' => 'Python 数据分析入门', 'content' => 'Python 是一种强大的编程语言，本文介绍如何使用 Python 进行数据分析...', 'views' => 1450],
    ];
    
    foreach ($articles as $index => $article) {
        $article['user_id'] = $userIds[array_rand($userIds)];
        $article['status'] = 1;
        $article['created_at'] = date('Y-m-d H:i:s', strtotime("-{$index} days"));
        DB::table('posts')->insert($article);
    }
    echo "✓ 插入 " . count($articles) . " 篇文章\n";
    
} catch (Exception $e) {
    echo "✗ 插入示例数据失败: " . $e->getMessage() . "\n";
    exit(1);
}

echo "[5/6] 正在配置缓存...\n";

try {
    // 配置缓存
    Cache::config([
        'driver' => 'file',
        'file' => ['path' => __DIR__ . '/../runtime/cache'],
    ]);
    
    // 设置欢迎缓存
    Cache::set('demo:welcome', [
        'message' => '欢迎使用 QuickAPI 演示项目！',
        'version' => '1.0.0',
        'created_at' => date('Y-m-d H:i:s'),
    ], 0); // 永不过期
    
    echo "✓ 缓存配置成功\n";
} catch (Exception $e) {
    echo "✗ 缓存配置失败: " . $e->getMessage() . "\n";
}

echo "[6/6] 正在清理...\n";

try {
    // 重置查询统计
    DB::resetStats();
    echo "✓ 清理完成\n";
} catch (Exception $e) {
    echo "✗ 清理失败: " . $e->getMessage() . "\n";
}

echo "\n========================================\n";
echo "数据库初始化完成！\n";
echo "========================================\n\n";

echo "📊 统计信息：\n";
echo "- 用户数量: " . DB::table('users')->count() . "\n";
echo "- 文章数量: " . DB::table('posts')->count() . "\n";
echo "- 分类数量: " . DB::table('categories')->count() . "\n";

echo "\n🔑 测试账号：\n";
echo "- 邮箱: zhangsan@example.com\n";
echo "- 密码: 123456\n";

echo "\n🌐 API地址：\n";
echo "- 基础URL: http://localhost/api/v1\n";
echo "- 用户列表: GET /api/v1/users\n";
echo "- 用户详情: GET /api/v1/users/{id}\n";
echo "- 文章列表: GET /api/v1/posts\n";
echo "- 登录: POST /api/v1/auth/login\n";

echo "\n💡 下一步：\n";
echo "1. 启动 PHP 内置服务器: php -S localhost:8080 -t public\n";
echo "2. 访问: http://localhost:8080/api/v1/health\n";
echo "\n";
