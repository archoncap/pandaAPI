# QuickAPI Demo 演示项目

## 📖 项目说明

本目录包含 QuickAPI 框架的完整演示项目，展示了框架的所有功能使用方法。

## 📁 目录结构

```
demo/
├── index.php          # 入口文件，包含所有路由定义
├── Controllers.php    # 控制器示例
├── config.php         # 配置文件
├── init_database.php  # 数据库初始化脚本
└── README.md          # 本文件
```

## 🚀 快速开始

### 1. 安装依赖

```bash
cd /path/to/quickapi
composer install
```

### 2. 创建数据库

```sql
CREATE DATABASE quickapi_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. 初始化数据库

```bash
php demo/init_database.php
```

### 4. 启动服务器

```bash
php -S localhost:8080 -t public
```

或者

```bash
php -S localhost:8080 demo/
```

### 5. 访问测试

```
http://localhost:8080/api/v1/health
```

## 📡 API 接口列表

### 公开接口

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/v1/health | 健康检查 |
| GET | /api/v1/users | 用户列表（支持分页） |
| GET | /api/v1/users/{id} | 用户详情 |
| POST | /api/v1/users | 创建用户 |
| PUT | /api/v1/users/{id} | 更新用户 |
| DELETE | /api/v1/users/{id} | 删除用户 |
| GET | /api/v1/posts | 文章列表 |
| GET | /api/v1/posts/{id} | 文章详情 |
| POST | /api/v1/posts | 创建文章 |
| GET | /api/v1/stats | 统计数据 |
| GET | /api/v1/cache/test | 缓存测试 |
| DELETE | /api/v1/cache | 清空缓存 |
| GET | /api/v1/sql | 原生SQL测试 |

### 认证接口

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | /api/v1/auth/login | 用户登录 |
| POST | /api/v1/auth/register | 用户注册 |
| GET | /api/v1/auth/me | 获取当前用户 |

### 需认证接口（需在请求头添加 Authorization）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/v1/private/profile | 个人资料 |
| GET | /api/v1/private/my-posts | 我的文章 |
| GET | /api/v1/private/debug | 性能统计 |

## 📝 使用示例

### 1. 创建用户

```bash
curl -X POST http://localhost:8080/api/v1/users \
  -H "Content-Type: application/json" \
  -d '{"name":"张三","email":"zhangsan@test.com","password":"123456"}'
```

### 2. 获取用户列表

```bash
curl http://localhost:8080/api/v1/users
curl http://localhost:8080/api/v1/users?page=2&per_page=10
```

### 3. 用户登录

```bash
curl -X POST http://localhost:8080/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"zhangsan@example.com","password":"123456"}'
```

### 4. 访问需认证接口

```bash
curl http://localhost:8080/api/v1/private/profile \
  -H "Authorization: Bearer {token}"
```

## 📚 功能演示

### 数据库操作

```php
// 基础查询
DB::table('users')->select();
DB::table('users')->where('id', 1)->find();

// 链式调用
DB::table('users')
    ->field('id, name, email')
    ->where('status', 1)
    ->order('created_at', 'desc')
    ->limit(10)
    ->select();

// 关联查询
DB::table('posts')
    ->field('posts.*, users.name as author')
    ->join('users', 'posts.user_id = users.id')
    ->select();

// 聚合查询
DB::table('users')->count();
DB::table('posts')->sum('views');
DB::table('posts')->avg('views');

// 分页
DB::table('users')->paginate(15, $page);

// 事务
DB::transaction(function() {
    // 操作
});
```

### 缓存操作

```php
// 基本操作
Cache::set('key', $value, 300);
Cache::get('key');
Cache::has('key');
Cache::delete('key');

// 记住模式
Cache::remember('key', function() {
    return DB::table('users')->select();
}, 300);

// 标签
Cache::tag('users')->set('user_1', $data);
Cache::tag('users')->flush();

// 原子操作
Cache::increment('counter');
Cache::decrement('counter');

// 加锁
Cache::lock('resource', 10);
Cache::unlock('resource');
```

### 路由定义

```php
// HTTP方法
Route::get('/path', $handler);
Route::post('/path', $handler);
Route::put('/path', $handler);
Route::delete('/path', $handler);

// 路由组
Route::group(['prefix' => '/api/v1'], function() {
    Route::get('/users', 'Controller@index');
});

// 中间件
Route::group(['middleware' => ['Auth']], function() {
    Route::get('/profile', 'Controller@profile');
});

// 参数约束
Route::get('/user/{id}', 'Controller@show')->whereNumber('id');

// 资源路由
Route::resource('users', 'UserController');
```

### 验证器

```php
$validator = new Validator($data, [
    'name' => 'required|string|min:2|max:50',
    'email' => 'required|email',
    'password' => 'required|string|min:6|confirmed',
    'age' => 'numeric|between:18,100',
]);

if ($validator->fails()) {
    return Response::json(['errors' => $validator->errors()], 422);
}

$validated = $validator->validated();
```

## 🔧 配置说明

编辑 `config.php` 文件来自定义配置：

```php
// 数据库配置
'database' => [
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => 'quickapi_demo',
    'username' => 'root',
    'password' => '',
]

// 缓存配置
'cache' => [
    'driver' => 'file',  // 支持: file / redis / memcache / openresty
    'file' => ['path' => __DIR__ . '/../runtime/cache'],
]
```

## 📊 测试账号

初始化后会创建以下测试账号：

| 邮箱 | 密码 |
|------|------|
| zhangsan@example.com | 123456 |
| lisi@example.com | 123456 |
| wangwu@example.com | 123456 |

## ❓ 问题排查

### 数据库连接失败

1. 检查 MySQL 服务是否启动
2. 确认数据库配置正确
3. 确保数据库已创建

### 缓存写入失败

1. 检查缓存目录是否存在
2. 确认目录有写入权限

### API 返回 404

1. 检查 URL 是否正确
2. 确认路由定义正确
3. 查看 `.htaccess` 或服务器配置

## 📄 许可证

MIT License
