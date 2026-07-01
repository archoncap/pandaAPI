# PandaAPI 框架 Demo 说明文档

## Demo 简介

本 Demo 是 PandaAPI（熊猫API）框架的完整功能展示入口，通过一个独立可运行的 PHP 文件 (`app/demo.php`) 演示框架的所有核心功能和技术优势。

文件涵盖以下功能模块：

- **PandaDB 数据库操作** -- 融合 ThinkPHP 链式查询和 PandaDB 快捷方法
- **缓存系统** -- 支持 File/Redis/Memcache/Openresty 四种驱动
- **验证器** -- 管道符规则，支持自定义消息
- **中间件** -- CORS 跨域、Auth 认证、Throttle 限流
- **公共函数** -- 统一响应、参数获取、Token 生成、密码加密等
- **无状态设计** -- 基于 Token 认证，天然支持水平扩展

---

## 环境要求

| 项目 | 要求 |
|------|------|
| PHP 版本 | >= 7.4 |
| PHP 扩展 | PDO、pdo_mysql（或 pdo_sqlite / pdo_pgsql） |
| Composer | 用于安装依赖（inhere/sroute） |
| 数据库 | MySQL 5.7+（可选，不连接数据库时缓存和工具函数仍可用） |
| Web 服务器 | PHP 内置服务器即可（`php -S`） |

---

## 安装步骤

### 1. 安装 Composer 依赖

```bash
cd /path/to/pandaapi
composer install
```

### 2. 配置数据库（可选）

编辑 `config/app.php`，修改数据库连接信息：

```php
'database' => [
    'driver'   => 'mysql',
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'database' => 'pandaapi',
    'username' => 'root',
    'password' => '',
    'prefix'   => 'pa_',
],
```

### 3. 启动开发服务器

```bash
php -S localhost:8080 /path/to/pandaapi/app/demo.php
```

### 4. 访问 Demo

浏览器打开或使用 curl 测试：

```bash
curl http://localhost:8080/demo/info
```

---

## API 接口列表

### 框架信息

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/demo/info` | 框架信息总览，展示核心特性 |
| GET | `/demo/health` | 健康检查，检测数据库和缓存连接状态 |
| GET | `/demo/stateless` | 无状态设计说明 |
| GET | `/demo/routes` | 获取所有 Demo 路由列表 |

### PandaDB 数据库 -- ThinkPHP 风格链式查询

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/demo/db/chain-select` | 链式查询：table/where/order/limit/select |
| GET | `/demo/db/chain-find?id=1` | 链式查询单条：find |
| GET | `/demo/db/name-prefix` | name() 自动添加表前缀 |

### PandaDB 数据库 -- PandaDB 快捷方法

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/demo/db/medoo-select` | PandaDB select 查询多条 |
| GET | `/demo/db/medoo-get?id=1` | PandaDB get 查询单条 |
| POST | `/demo/db/medoo-insert` | PandaDB insert 插入数据 |
| POST | `/demo/db/medoo-update` | PandaDB update 更新数据 |
| POST | `/demo/db/medoo-delete` | PandaDB delete 删除数据 |
| GET | `/demo/db/medoo-aggregate` | 聚合函数 count/sum/avg/max/min |

### PandaDB 数据库 -- 高级功能

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/demo/db/join` | JOIN 关联查询（LEFT JOIN） |
| GET | `/demo/db/paginate?page=1&per_page=5` | 分页查询 paginate |
| GET | `/demo/db/raw-query` | 原生 SQL 查询 query |
| POST | `/demo/db/raw-execute` | 原生 SQL 执行 execute |
| POST | `/demo/db/transaction` | 事务操作（回调方式） |
| GET | `/demo/db/advanced-where` | WHERE 高级条件（whereIn/whereBetween/whereLike/whereNull/whereNotNull/whereOr） |
| GET | `/demo/db/slow-queries` | 慢查询统计 |
| GET | `/demo/db/sql-log` | SQL 日志查看 |

### 缓存系统

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/demo/cache/set` | 缓存设置 set（Body: key, value, ttl） |
| GET | `/demo/cache/get?key=demo_key` | 缓存读取 get |
| GET | `/demo/cache/has?key=demo_key` | 缓存检测 has |
| POST | `/demo/cache/delete` | 缓存删除 delete/forget |
| GET | `/demo/cache/remember?key=expensive_query` | 缓存回源 remember |
| POST | `/demo/cache/increment` | 原子递增 increment |
| POST | `/demo/cache/decrement` | 原子递减 decrement |
| POST | `/demo/cache/batch` | 批量操作 setMultiple/getMultiple/deleteMultiple |
| POST | `/demo/cache/tag` | 标签缓存 tag |

### 验证器

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/demo/validator` | 验证器演示（required/email/min/max/numeric/between/confirmed） |

### 中间件

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/demo/middleware/cors` | CORS 跨域中间件 |
| GET | `/demo/middleware/auth` | Auth 认证中间件（需 Authorization 请求头） |
| GET | `/demo/middleware/throttle` | Throttle 限流中间件 |

### 公共函数

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/demo/helper/json-response` | json_success/json_error/json_page 响应函数 |
| POST | `/demo/helper/get-input` | get_input 获取参数 |
| GET | `/demo/helper/generate-token` | generate_token 生成 Token |
| POST | `/demo/helper/password` | password_encrypt/password_check 密码加密验证 |
| GET | `/demo/helper/client-ip` | get_client_ip 获取客户端 IP |
| GET | `/demo/helper/format-date` | format_date 格式化日期 |
| GET | `/demo/helper/dd` | dd 调试函数 |
| GET | `/demo/helper/response-json` | response_json 统一 JSON 输出 |

---

## 快速测试命令

以下命令假设服务器已启动在 `http://localhost:8080`。

### 框架信息

```bash
# 查看框架信息
curl http://localhost:8080/demo/info

# 健康检查
curl http://localhost:8080/demo/health

# 查看所有路由
curl http://localhost:8080/demo/routes
```

### 数据库操作

```bash
# ThinkPHP 风格链式查询
curl http://localhost:8080/demo/db/chain-select

# 查询单条记录
curl http://localhost:8080/demo/db/chain-find?id=1

# name() 表前缀
curl http://localhost:8080/demo/db/name-prefix

# PandaDB 快捷方法查询
curl http://localhost:8080/demo/db/medoo-select
curl http://localhost:8080/demo/db/medoo-get?id=1

# 聚合函数
curl http://localhost:8080/demo/db/medoo-aggregate

# 插入数据
curl -X POST http://localhost:8080/demo/db/medoo-insert \
  -d 'username=demo_user&email=demo@test.com'

# 更新数据
curl -X POST http://localhost:8080/demo/db/medoo-update \
  -d 'id=1&username=new_name'

# 删除数据
curl -X POST http://localhost:8080/demo/db/medoo-delete \
  -d 'id=1'

# JOIN 关联查询
curl http://localhost:8080/demo/db/join

# 分页查询
curl "http://localhost:8080/demo/db/paginate?page=1&per_page=5"

# 原生 SQL 查询
curl http://localhost:8080/demo/db/raw-query

# 原生 SQL 执行
curl -X POST http://localhost:8080/demo/db/raw-execute

# 事务操作
curl -X POST http://localhost:8080/demo/db/transaction

# WHERE 高级条件
curl http://localhost:8080/demo/db/advanced-where

# 慢查询统计
curl http://localhost:8080/demo/db/slow-queries

# SQL 日志
curl http://localhost:8080/demo/db/sql-log
```

### 缓存操作

```bash
# 设置缓存
curl -X POST http://localhost:8080/demo/cache/set \
  -d 'key=hello&value=world&ttl=300'

# 读取缓存
curl "http://localhost:8080/demo/cache/get?key=hello"

# 检测缓存
curl "http://localhost:8080/demo/cache/has?key=hello"

# 缓存回源
curl "http://localhost:8080/demo/cache/remember?key=expensive_query"

# 原子递增
curl -X POST http://localhost:8080/demo/cache/increment \
  -d 'key=counter&step=1'

# 原子递减
curl -X POST http://localhost:8080/demo/cache/decrement \
  -d 'key=counter&step=1'

# 批量操作
curl -X POST http://localhost:8080/demo/cache/batch

# 标签缓存
curl -X POST http://localhost:8080/demo/cache/tag

# 删除缓存
curl -X POST http://localhost:8080/demo/cache/delete \
  -d 'key=hello'
```

### 验证器

```bash
# 验证通过
curl -X POST http://localhost:8080/demo/validator \
  -d 'username=test&email=test@test.com&age=25&password=123456&password_confirmation=123456'

# 验证失败（缺少参数）
curl -X POST http://localhost:8080/demo/validator
```

### 中间件

```bash
# CORS 跨域
curl http://localhost:8080/demo/middleware/cors

# Auth 认证（需携带 Token）
curl http://localhost:8080/demo/middleware/auth \
  -H "Authorization: Bearer your_token_here"

# Throttle 限流
curl http://localhost:8080/demo/middleware/throttle
```

### 公共函数

```bash
# JSON 响应格式
curl http://localhost:8080/demo/helper/json-response

# 获取参数
curl -X POST http://localhost:8080/demo/helper/get-input \
  -d 'name=world'

# 生成 Token
curl http://localhost:8080/demo/helper/generate-token

# 密码加密验证
curl -X POST http://localhost:8080/demo/helper/password \
  -d 'password=my_secret'

# 获取客户端 IP
curl http://localhost:8080/demo/helper/client-ip

# 格式化日期
curl http://localhost:8080/demo/helper/format-date

# 调试输出
curl http://localhost:8080/demo/helper/dd
```

---

## 框架技术优势总结

| 特性 | 说明 |
|------|------|
| **双风格数据库** | 融合 ThinkPHP 链式操作和 PandaDB 快捷方法，一套代码两种写法 |
| **多缓存驱动** | File / Redis / Memcache / Openresty 四种后端，一行配置切换 |
| **无状态架构** | 不依赖 Session，基于 Token 认证，天然支持水平扩展和负载均衡 |
| **高效路由** | 基于 inhere/sroute，支持分组、命名、资源路由、参数约束 |
| **灵活中间件** | CORS / Auth / Throttle 开箱即用，支持自定义扩展 |
| **安全防护** | PDO 预处理语句防 SQL 注入，password_hash 加密存储 |
| **慢查询监控** | 自动记录超过阈值的 SQL，内置查询日志和统计 |
| **标签缓存** | 按模块分组管理缓存，支持按标签批量清除 |
| **原子操作** | increment/decrement 线程安全，适用于高并发计数场景 |
| **管道验证** | 管道符分隔规则，支持自定义错误消息 |
| **统一响应** | json_success/json_error/json_page 标准化 API 输出格式 |
| **零依赖核心** | 核心功能仅依赖 PHP 和 PDO，无需重量级框架 |
