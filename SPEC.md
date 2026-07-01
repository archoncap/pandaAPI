# High-Performance API Framework Specification

## 1. 项目概述

**项目名称**: QuickAPI  
**项目类型**: 高性能RESTful API框架  
**PHP版本要求**: PHP 7.4+  
**核心目标**: 提供类似ThinkPHP5的极简链式数据库操作，同时实现极致的性能优化

---

## 2. 核心架构设计

### 2.1 目录结构

```
quickapi/
├── src/
│   ├── Core/
│   │   ├── App.php              # 应用核心类
│   │   ├── Config.php           # 配置管理
│   │   └── Request.php          # 请求处理
│   │   └── Response.php         # 响应处理
│   ├── Database/
│   │   ├── DB.php               # 数据库入口（静态调用）
│   │   ├── Connection.php       # 数据库连接管理
│   │   ├── Builder.php          # 查询构建器
│   │   ├── PandaDBWrapper.php     # PandaDB封装类
│   │   └── Pool/
│   │       └── DBConnectionPool.php  # 连接池
│   ├── Cache/
│   │   ├── Cache.php            # 缓存入口
│   │   ├── Drivers/
│   │   │   ├── FileCache.php    # 文件缓存
│   │   │   ├── MemcacheCache.php
│   │   │   ├── RedisCache.php
│   │   │   └── OpenrestyCache.php
│   │   └── QueryCache.php       # 查询缓存
│   ├── Route/
│   │   ├── Router.php           # 路由核心
│   │   ├── RouteGroup.php       # 路由组
│   │   └── Middleware.php       # 中间件管理
│   └── Exception/
│       └── ApiException.php     # 异常处理
├── config/
│   └── database.php             # 数据库配置
│   └── cache.php                # 缓存配置
│   └── route.php                # 路由配置
├── public/
│   └── index.php                # 入口文件
├── tests/
│   └── Unit/
│       ├── DBTest.php
│       ├── CacheTest.php
│       └── RouteTest.php
├── composer.json
└── README.md
```

### 2.2 技术栈

| 组件 | 技术选型 | 版本要求 |
|------|----------|----------|
| 数据库ORM | PandaDB | ^1.0 |
| 路由 | inhere/sroute | ^2.0 |
| 缓存 | 支持多种后端 | - |
| PHP版本 | PHP | 7.4+ |

---

## 3. 功能详细设计

### 3.1 数据库模块 (Database)

#### 3.1.1 DB静态入口类
```php
// 核心调用方式 - 类似ThinkPHP5
DB::table('user')->select();
DB::table('user')->where('id', 1)->find();
DB::table('user')->insert(['name' => 'test']);
DB::table('user')->where('id', 1)->update(['name' => 'updated']);
DB::table('user')->where('id', 1)->delete();
```

#### 3.1.2 查询构建器链式调用
```php
// 支持的链式方法
DB::table('user')
    ->field('id, name, email')
    ->where('status', 1)
    ->where('age', '>', 18)
    ->order('id', 'desc')
    ->limit(10)
    ->select();

// 关联查询
DB::table('user')
    ->join('profile', 'user.id = profile.user_id')
    ->select();

// 分页查询
DB::table('user')->paginate(15, $page);
```

#### 3.1.3 连接池管理
```php
class DBConnectionPool {
    private $maxConnections = 10;
    private $minConnections = 2;
    private $connections = [];
    
    public function getConnection(): PDO;
    public function releaseConnection(PDO $conn): void;
    public function closeAll(): void;
}
```

#### 3.1.4 性能统计
```php
// 内置性能监控
DB::getQueryCount();      // 查询次数
DB::getLastQueryTime();   // 最后查询耗时(ms)
DB::getTotalQueryTime();  // 总查询耗时(ms)
DB::getSlowQueries();     // 慢查询日志(>100ms)
```

### 3.2 缓存模块 (Cache)

#### 3.2.1 缓存接口
```php
interface CacheInterface {
    public function get(string $key, $default = null);
    public function set(string $key, $value, int $ttl = 0): bool;
    public function delete(string $key): bool;
    public function clear(): bool;
    public function has(string $key): bool;
}
```

#### 3.2.2 支持的缓存驱动

| 驱动 | 说明 | 配置项 |
|------|------|--------|
| File | 文件缓存 | `path` |
| Memcache | Memcache缓存 | `host`, `port` |
| Redis | Redis缓存 | `host`, `port`, `password` |
| Openresty | OpenResty共享内存 | `host`, `port` |

#### 3.2.3 查询缓存
```php
// 自动缓存查询结果
Cache::query('user_list', function() {
    return DB::table('user')->where('status', 1)->select();
}, 300); // 缓存300秒
```

### 3.3 路由模块 (Route)

#### 3.3.1 路由定义
```php
// 基础路由
Route::get('/user/{id}', 'UserController@show');
Route::post('/user', 'UserController@create');
Route::put('/user/{id}', 'UserController@update');
Route::delete('/user/{id}', 'UserController@delete');

// 路由组
Route::group(['prefix' => '/api/v1'], function() {
    Route::get('/users', 'UserController@index');
    Route::get('/users/{id}', 'UserController@show');
});

// 中间件
Route::group(['middleware' => ['Auth', 'Log']], function() {
    Route::get('/admin/dashboard', 'AdminController@dashboard');
});
```

#### 3.3.2 路由特性
- O(1)时间复杂度路由匹配
- 支持命名路由
- 支持动态路径参数
- 支持HTTP方法限制
- 支持路由组和前缀

### 3.4 中间件系统

```php
// 全局中间件
$app->middleware(['Cors', 'Log']);

// 路由中间件
Route::get('/profile', 'UserController@profile')
    ->middleware(['Auth']);

// 自定义中间件
class Auth {
    public function handle($request, $next) {
        if (!$this->checkToken($request)) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }
        return $next($request);
    }
}
```

---

## 4. 性能优化措施

### 4.1 连接池
- 最大连接数: 10
- 最小连接数: 2
- 空闲超时: 60秒
- 连接超时: 5秒

### 4.2 查询优化
- PDO预处理语句
- 查询结果缓存
- 延迟执行
- 批量插入优化

### 4.3 无状态设计
- 关闭PHP Session
- Token认证
- 请求级数据存储

### 4.4 路由优化
- 静态路由优先匹配
- 路由缓存(可选)
- 贪婪匹配优化

---

## 5. 配置设计

### 5.1 数据库配置 (database.php)
```php
return [
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => 'quickapi',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'prefix' => '',
    'pool' => [
        'max' => 10,
        'min' => 2,
    ],
];
```

### 5.2 缓存配置 (cache.php)
```php
return [
    'driver' => 'redis',
    'redis' => [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => null,
        'database' => 0,
    ],
    'memcache' => [
        'host' => '127.0.0.1',
        'port' => 11211,
    ],
    'file' => [
        'path' => '/tmp/cache',
    ],
    'openresty' => [
        'host' => '127.0.0.1',
        'port' => 8099,
    ],
    'default_ttl' => 3600,
];
```

---

## 6. 错误处理

### 6.1 异常类
```php
class ApiException extends Exception {
    public $code = 500;
    public $message = 'Internal Server Error';
    public $data = null;
}
```

### 6.2 HTTP状态码映射
| 异常类型 | 状态码 |
|----------|--------|
| BadRequest | 400 |
| Unauthorized | 401 |
| Forbidden | 403 |
| NotFound | 404 |
| MethodNotAllowed | 405 |
| ServerError | 500 |

---

## 7. 验收标准

### 7.1 功能验收
- [ ] DB::table()->select() 链式调用正常工作
- [ ] 支持所有基本CRUD操作
- [ ] 连接池正确管理连接
- [ ] 四种缓存驱动均可正常使用
- [ ] 路由匹配正确执行
- [ ] 中间件链正确执行

### 7.2 性能验收
- [ ] 单次查询 < 10ms (不含网络)
- [ ] 路由匹配 < 1ms
- [ ] 支持100+路由注册

### 7.3 代码质量
- [ ] PSR-4自动加载
- [ ] 单元测试覆盖率 > 80%
- [ ] 无XSS/SQL注入漏洞

---

## 8. 示例用法

### 8.1 完整使用示例
```php
<?php
// public/index.php
require __DIR__ . '/../vendor/autoload.php';

use QuickAPI\Core\App;
use QuickAPI\Database\DB;
use QuickAPI\Route\Route;
use QuickAPI\Cache\Cache;
use QuickAPI\Core\Response;

// 初始化应用
$app = new App();

// 配置数据库
DB::config([
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'database' => 'test',
    'username' => 'root',
    'password' => ''
]);

// 定义路由
Route::get('/users', function() {
    return Response::json(DB::table('user')->select());
});

Route::get('/user/{id}', function($id) {
    $user = DB::table('user')->where('id', $id)->find();
    if (!$user) {
        return Response::json(['error' => 'User not found'], 404);
    }
    return Response::json($user);
});

Route::post('/user', function() {
    $data = Request::all();
    $id = DB::table('user')->insert($data);
    return Response::json(['id' => $id], 201);
});

// 运行应用
$app->run();
```

### 8.2 控制器示例
```php
<?php
namespace App\Controller;

use QuickAPI\Database\DB;
use QuickAPI\Core\Response;

class UserController {
    public function index() {
        $users = Cache::remember('users_list', function() {
            return DB::table('user')->where('status', 1)->select();
        }, 300);
        return Response::json($users);
    }
    
    public function show($id) {
        $user = DB::table('user')->where('id', $id)->find();
        return $user ? Response::json($user) : Response::json(['error' => 'Not found'], 404);
    }
    
    public function create() {
        $data = Request::all();
        $id = DB::table('user')->insert($data);
        return Response::json(['id' => $id], 201);
    }
}
```
