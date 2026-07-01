# 熊猫API框架 (PandaAPI)

高性能 PHP 7.4+ API 框架，融合 ThinkPHP 的 Db 操作方式，原生 PDO 实现 PandaDB 全部功能。

## 特性

- **PandaDB 数据库类** - 融合 ThinkPHP 的链式调用和 PandaDB 快捷方法
- **多缓存后端** - 支持 File / Redis / Memcache / OpenResty
- **极速路由** - 基于 inhere/sroute，O(1) 时间复杂度
- **无状态设计** - 关闭 PHP Session，最大化性能
- **PDO 预处理** - 防止 SQL 注入

## 安装

```bash
composer require pandaapi/framework
```

## 快速开始

```php
<?php
require_once 'vendor/autoload.php';

use PandaAPI\Database\PandaDB;
use PandaAPI\Route\Route;
use PandaAPI\Core\Response;

// 配置数据库
PandaDB::config([
    'driver'   => 'mysql',
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'database' => 'test',
    'username' => 'root',
    'password' => '',
]);

// 定义路由
Route::get('/users', function() {
    $users = PandaDB::table('users')->select();
    return Response::json($users);
});

// 运行应用
$app = new PandaAPI\Core\App();
$app->run();
```

## PandaDB 使用示例

### ThinkPHP 风格链式调用

```php
// 查询所有用户
PandaDB::table('users')->select();

// 条件查询
PandaDB::table('users')
    ->where('status', 1)
    ->order('created_at', 'desc')
    ->limit(10)
    ->select();

// 分页查询
$result = PandaDB::table('users')
    ->where('status', 1)
    ->paginate(15, $page);

// 插入数据
$id = PandaDB::table('users')->insert([
    'name' => '张三',
    'email' => 'zhangsan@test.com',
]);

// 更新数据
PandaDB::table('users')
    ->where('id', 1)
    ->update(['name' => '李四']);

// 删除数据
PandaDB::table('users')
    ->where('id', 1)
    ->delete();
```

### PandaDB 快捷方法

```php
// 查询多条
$users = PandaDB::select('users', ['id', 'name'], ['status' => 1]);

// 查询单条
$user = PandaDB::get('users', ['id', 'name'], ['id' => 1]);

// 插入
$id = PandaDB::insert('users', [
    'name' => '张三',
    'email' => 'zhangsan@test.com',
]);

// 更新
$affected = PandaDB::update('users', ['name' => '李四'], ['id' => 1]);

// 删除
$affected = PandaDB::delete('users', ['id' => 1]);

// 统计
$count = PandaDB::count('users', ['status' => 1]);

// 聚合
$sum = PandaDB::sum('orders', 'amount', ['status' => 1]);
$avg = PandaDB::avg('scores', 'score');
$max = PandaDB::max('products', 'price');
$min = PandaDB::min('products', 'price');
```

### 事务操作

```php
// 方式一：回调事务
PandaDB::transaction(function() {
    $userId = PandaDB::table('users')->insert(['name' => '张三']);
    PandaDB::table('logs')->insert(['user_id' => $userId, 'action' => 'register']);
});

// 方式二：手动事务
PandaDB::beginTransaction();
try {
    PandaDB::table('users')->insert(['name' => '张三']);
    PandaDB::commit();
} catch (Exception $e) {
    PandaDB::rollback();
}
```

### 原生 SQL

```php
// 查询
$rows = PandaDB::query('SELECT * FROM users WHERE status = ?', [1]);

// 执行
$affected = PandaDB::execute('UPDATE users SET status = ? WHERE id = ?', [1, 2]);
```

## 目录结构

```
pandaapi/
├── src/
│   ├── Core/            # 核心类 (App, Request, Response)
│   ├── Database/        # PandaDB 数据库类
│   ├── Cache/           # 缓存系统
│   ├── Route/           # 路由系统
│   ├── Middleware/      # 中间件
│   └── Validation/      # 验证器
├── demo/                # 完整演示项目
└── composer.json
```

## 许可证

MIT License
# pandaAPI
