<?php
/**
 * ============================================================================
 *  PandaAPI 框架完整功能 Demo
 * ============================================================================
 *
 *  本文件是熊猫API框架的独立可运行示例，展示框架的全部功能和技术优势。
 *  所有路由均可通过 HTTP 请求直接调用，用于快速体验和验证框架能力。
 *
 *  启动方式：
 *    php -S localhost:8080 /workspace/pandaapi/app/demo.php
 *
 *  测试方式：
 *    curl http://localhost:8080/demo/info
 *    curl http://localhost:8080/demo/db/chain-select
 *    curl -X POST http://localhost:8080/demo/cache/set -d 'key=hello&value=world'
 *
 * ============================================================================
 */

// ============================================================================
// 第一部分：引入依赖和初始化
// ============================================================================

// 引入 Composer 自动加载
require_once dirname(__DIR__) . '/vendor/autoload.php';

// 引入公共函数库（包含 json_success/json_error/get_input/dd 等全局函数）
require_once __DIR__ . '/common/functions.php';

// 引入框架核心类
use PandaAPI\Database\PandaDB;
use PandaAPI\Cache\Cache;
use PandaAPI\Validation\Validator;
use PandaAPI\Route\Route;
use PandaAPI\Route\RouteItem;
use PandaAPI\Middleware\CorsMiddleware;
use PandaAPI\Middleware\AuthMiddleware;
use PandaAPI\Middleware\ThrottleMiddleware;

// ============================================================================
// 第二部分：配置数据库和缓存
// ============================================================================

/**
 * 配置数据库连接
 * PandaDB 支持 MySQL / PgSQL / SQLite 多种驱动
 * 采用单例模式 + PDO 预处理语句，确保连接安全和查询效率
 */
PandaDB::config([
    'driver'    => 'mysql',
    'host'      => '127.0.0.1',
    'port'      => 3306,
    'database'  => 'pandaapi',
    'username'  => 'root',
    'password'  => '',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => 'pa_',       // 表前缀，配合 name() 方法使用
    'options'   => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,  // 禁用模拟预处理，防止SQL注入
    ],
]);

/**
 * 配置缓存系统
 * 支持 file / redis / memcache / openresty 四种驱动
 * 无状态设计：缓存不依赖 Session，所有数据通过 Token/Key 访问
 */
Cache::config([
    'driver' => 'file',
    'file'   => [
        'path' => dirname(__DIR__) . '/runtime/cache',
    ],
]);

/**
 * 设置慢查询阈值（毫秒）
 * 超过此阈值的查询将被自动记录到慢查询日志
 */
PandaDB::setSlowThreshold(100);

// ============================================================================
// 第三部分：注册路由
// ============================================================================

// --------------------------------------------------------------------------
// 3.0 框架信息
// --------------------------------------------------------------------------

/**
 * 框架信息总览
 * 展示 PandaAPI 框架的核心特性和技术优势
 *
 * GET /demo/info
 */
Route::get('/demo/info', function () {
    response_json(json_success([
        'framework'    => 'PandaAPI',
        'version'      => '1.0.0',
        'description'  => '高性能无状态 API 框架',
        'features'     => [
            'PandaDB 数据库' => '融合 ThinkPHP 链式操作 + PandaDB 快捷方法',
            '多缓存驱动'     => 'File / Redis / Memcache / Openresty',
            '验证器'         => '支持 required/email/min/max/numeric/between/confirmed 等',
            '中间件'         => 'CORS 跨域 / Auth 认证 / Throttle 限流',
            '路由系统'       => '基于 inhere/sroute 高效匹配，支持分组/命名/资源路由',
            '无状态设计'     => '不依赖 Session，基于 Token 认证',
            '慢查询统计'     => '自动记录超过阈值的 SQL 查询',
            'SQL 日志'       => '完整的查询日志，便于调试和性能分析',
        ],
        'php_required' => '>= 7.4',
    ]));
});

/**
 * 框架健康检查
 * 检测数据库连接和缓存驱动是否正常
 *
 * GET /demo/health
 */
Route::get('/demo/health', function () {
    $dbOk   = false;
    $cacheOk = false;

    // 检测数据库连接
    try {
        PandaDB::getPdo();
        $dbOk = true;
    } catch (\Throwable $e) {
        // 数据库不可用
    }

    // 检测缓存驱动
    try {
        Cache::set('_health_check', 'ok', 10);
        $cacheOk = Cache::get('_health_check') === 'ok';
        Cache::delete('_health_check');
    } catch (\Throwable $e) {
        // 缓存不可用
    }

    $allOk = $dbOk && $cacheOk;

    response_json([
        'code'    => $allOk ? 200 : 503,
        'message' => $allOk ? 'All systems operational' : 'Some services unavailable',
        'data'    => [
            'database' => $dbOk ? 'connected' : 'unavailable',
            'cache'    => $cacheOk ? 'connected' : 'unavailable',
            'status'   => $allOk ? 'healthy' : 'degraded',
        ],
    ], $allOk ? 200 : 503);
});

// --------------------------------------------------------------------------
// 3.1 PandaDB 数据库操作 - ThinkPHP 风格链式查询
// --------------------------------------------------------------------------

/**
 * 链式查询：table/where/order/limit/select
 * ThinkPHP 风格的链式调用，流畅优雅
 *
 * GET /demo/db/chain-select
 */
Route::get('/demo/db/chain-select', function () {
    try {
        $users = PandaDB::table('users')
            ->field('id, username, email, created_at')
            ->where('status', 1)
            ->order('id', 'DESC')
            ->limit(10)
            ->select();

        response_json(json_success([
            'method'  => 'PandaDB::table()->field()->where()->order()->limit()->select()',
            'count'   => count($users),
            'data'    => $users,
        ]));
    } catch (\Throwable $e) {
        response_json(json_error('数据库查询失败: ' . $e->getMessage(), 500));
    }
});

/**
 * 链式查询：find 查询单条记录
 * 自动 LIMIT 1，返回单条数据或 null
 *
 * GET /demo/db/chain-find?id=1
 */
Route::get('/demo/db/chain-find', function () {
    $id = get_input('id', 1);

    try {
        $user = PandaDB::table('users')
            ->field('id, username, email')
            ->where('id', $id)
            ->find();

        response_json(json_success([
            'method' => 'PandaDB::table()->field()->where()->find()',
            'data'   => $user,
        ]));
    } catch (\Throwable $e) {
        response_json(json_error('数据库查询失败: ' . $e->getMessage(), 500));
    }
});

/**
 * name() 方法：自动添加表前缀
 * 使用 name('users') 等同于 table('pa_users')（当 prefix 为 'pa_' 时）
 *
 * GET /demo/db/name-prefix
 */
Route::get('/demo/db/name-prefix', function () {
    try {
        // name() 自动拼接表前缀，无需手动输入完整表名
        $users = PandaDB::name('users')
            ->field('id, username')
            ->limit(5)
            ->select();

        response_json(json_success([
            'method'    => 'PandaDB::name("users")',
            'note'      => '自动添加表前缀 pa_，实际查询表 pa_users',
            'count'     => count($users),
            'data'      => $users,
        ]));
    } catch (\Throwable $e) {
        response_json(json_error('数据库查询失败: ' . $e->getMessage(), 500));
    }
});

// --------------------------------------------------------------------------
// 3.2 PandaDB 数据库操作 - PandaDB 快捷方法
// --------------------------------------------------------------------------

/**
 * PandaDB 快捷方法：select 查询多条记录
 * 静态方法，一行代码完成查询
 *
 * GET /demo/db/medoo-select
 */
Route::get('/demo/db/medoo-select', function () {
    try {
        $users = PandaDB::select('users', ['id', 'username', 'email'], [
            'status' => 1,
            'ORDER'  => ['id' => 'DESC'],
            'LIMIT'  => 10,
        ]);

        response_json(json_success([
            'method' => 'PandaDB::select(table, columns, where)',
            'count'  => count($users),
            'data'   => $users,
        ]));
    } catch (\Throwable $e) {
        response_json(json_error('数据库查询失败: ' . $e->getMessage(), 500));
    }
});

/**
 * PandaDB 快捷方法：get 查询单条记录
 *
 * GET /demo/db/medoo-get?id=1
 */
Route::get('/demo/db/medoo-get', function () {
    $id = get_input('id', 1);

    try {
        $user = PandaDB::get('users', ['id', 'username', 'email'], [
            'id' => $id,
        ]);

        response_json(json_success([
            'method' => 'PandaDB::get(table, columns, where)',
            'data'   => $user,
        ]));
    } catch (\Throwable $e) {
        response_json(json_error('数据库查询失败: ' . $e->getMessage(), 500));
    }
});

/**
 * PandaDB 快捷方法：insert 插入数据
 *
 * POST /demo/db/medoo-insert
 * Body: username=demo_user&email=demo@test.com
 */
Route::post('/demo/db/medoo-insert', function () {
    $username = get_input('username', 'demo_user');
    $email    = get_input('email', 'demo@test.com');

    try {
        $insertId = PandaDB::insert('users', [
            'username'   => $username,
            'email'      => $email,
            'status'     => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        response_json(json_success([
            'method'   => 'PandaDB::insert(table, data)',
            'insert_id' => $insertId,
        ], '插入成功'));
    } catch (\Throwable $e) {
        response_json(json_error('插入失败: ' . $e->getMessage(), 500));
    }
});

/**
 * PandaDB 快捷方法：update 更新数据
 *
 * POST /demo/db/medoo-update
 * Body: id=1&username=new_name
 */
Route::post('/demo/db/medoo-update', function () {
    $id       = get_input('id');
    $username = get_input('username');

    if (!$id || !$username) {
        response_json(json_error('参数 id 和 username 必填', 400));
    }

    try {
        $affected = PandaDB::update('users', [
            'username'   => $username,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);

        response_json(json_success([
            'method'   => 'PandaDB::update(table, data, where)',
            'affected' => $affected,
        ], '更新成功'));
    } catch (\Throwable $e) {
        response_json(json_error('更新失败: ' . $e->getMessage(), 500));
    }
});

/**
 * PandaDB 快捷方法：delete 删除数据
 *
 * POST /demo/db/medoo-delete
 * Body: id=1
 */
Route::post('/demo/db/medoo-delete', function () {
    $id = get_input('id');

    if (!$id) {
        response_json(json_error('参数 id 必填', 400));
    }

    try {
        $affected = PandaDB::delete('users', ['id' => $id]);

        response_json(json_success([
            'method'   => 'PandaDB::delete(table, where)',
            'affected' => $affected,
        ], '删除成功'));
    } catch (\Throwable $e) {
        response_json(json_error('删除失败: ' . $e->getMessage(), 500));
    }
});

/**
 * PandaDB 快捷方法：聚合函数 count/sum/avg/max/min
 * 展示所有聚合统计方法
 *
 * GET /demo/db/medoo-aggregate
 */
Route::get('/demo/db/medoo-aggregate', function () {
    try {
        $stats = [
            'count' => PandaDB::count('users', ['status' => 1]),
            'sum'   => PandaDB::sum('users', 'id', ['status' => 1]),
            'avg'   => PandaDB::avg('users', 'id', ['status' => 1]),
            'max'   => PandaDB::max('users', 'id', ['status' => 1]),
            'min'   => PandaDB::min('users', 'id', ['status' => 1]),
        ];

        response_json(json_success([
            'method' => 'PandaDB::count/sum/avg/max/min(table, column, where)',
            'data'   => $stats,
        ]));
    } catch (\Throwable $e) {
        response_json(json_error('聚合查询失败: ' . $e->getMessage(), 500));
    }
});

// --------------------------------------------------------------------------
// 3.3 PandaDB 数据库操作 - JOIN 关联查询
// --------------------------------------------------------------------------

/**
 * JOIN 关联查询
 * 支持 INNER JOIN / LEFT JOIN / RIGHT JOIN
 *
 * GET /demo/db/join
 */
Route::get('/demo/db/join', function () {
    try {
        // 查询用户及其关联的文章
        $results = PandaDB::table('users')
            ->field('users.id, users.username, posts.title, posts.created_at as post_time')
            ->leftJoin('posts', 'users.id = posts.user_id')
            ->order('users.id', 'ASC')
            ->limit(20)
            ->select();

        response_json(json_success([
            'method' => 'PandaDB::table()->leftJoin()->select()',
            'note'   => 'LEFT JOIN 关联 users 和 posts 表',
            'count'  => count($results),
            'data'   => $results,
        ]));
    } catch (\Throwable $e) {
        response_json(json_error('JOIN 查询失败: ' . $e->getMessage(), 500));
    }
});

// --------------------------------------------------------------------------
// 3.4 PandaDB 数据库操作 - 分页查询
// --------------------------------------------------------------------------

/**
 * 分页查询 paginate
 * 自动计算总页数，返回标准分页结构
 *
 * GET /demo/db/paginate?page=1&per_page=5
 */
Route::get('/demo/db/paginate', function () {
    $page    = (int) get_input('page', 1);
    $perPage = (int) get_input('per_page', 5);

    try {
        $result = PandaDB::table('users')
            ->field('id, username, email, created_at')
            ->where('status', 1)
            ->order('id', 'DESC')
            ->paginate($perPage, $page);

        response_json(json_page(
            $result['data'],
            $result['total'],
            $result['current_page'],
            $result['per_page']
        ));
    } catch (\Throwable $e) {
        response_json(json_error('分页查询失败: ' . $e->getMessage(), 500));
    }
});

// --------------------------------------------------------------------------
// 3.5 PandaDB 数据库操作 - 原生 SQL
// --------------------------------------------------------------------------

/**
 * 原生 SQL 查询：query
 * 使用预处理语句防止 SQL 注入
 *
 * GET /demo/db/raw-query
 */
Route::get('/demo/db/raw-query', function () {
    try {
        $results = PandaDB::query(
            'SELECT * FROM users WHERE status = ? ORDER BY id DESC LIMIT ?',
            [1, 5]
        );

        response_json(json_success([
            'method' => 'PandaDB::query(sql, bindings)',
            'note'   => '原生 SQL 查询，使用参数绑定防止注入',
            'count'  => count($results),
            'data'   => $results,
        ]));
    } catch (\Throwable $e) {
        response_json(json_error('原生查询失败: ' . $e->getMessage(), 500));
    }
});

/**
 * 原生 SQL 执行：execute
 * 用于 INSERT/UPDATE/DELETE 等写操作
 *
 * POST /demo/db/raw-execute
 */
Route::post('/demo/db/raw-execute', function () {
    try {
        $affected = PandaDB::execute(
            'UPDATE users SET updated_at = ? WHERE status = ?',
            [date('Y-m-d H:i:s'), 1]
        );

        response_json(json_success([
            'method'   => 'PandaDB::execute(sql, bindings)',
            'affected' => $affected,
        ], '执行成功'));
    } catch (\Throwable $e) {
        response_json(json_error('原生执行失败: ' . $e->getMessage(), 500));
    }
});

// --------------------------------------------------------------------------
// 3.6 PandaDB 数据库操作 - 事务操作
// --------------------------------------------------------------------------

/**
 * 事务操作：transaction 回调方式
 * 自动提交/回滚，异常时自动回滚
 *
 * POST /demo/db/transaction
 */
Route::post('/demo/db/transaction', function () {
    try {
        $result = PandaDB::transaction(function (PandaDB $db) {
            // 操作1：插入用户
            $userId = $db->setTable('users')->insert([
                'username'   => 'transaction_user',
                'email'      => 'trans@test.com',
                'status'     => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            // 操作2：插入关联记录
            $db->setTable('user_profiles')->insert([
                'user_id' => $userId,
                'bio'     => '通过事务创建的用户',
            ]);

            // 操作3：更新统计
            $db->setTable('users')->where('id', $userId)->update([
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            return ['user_id' => $userId, 'steps' => 3];
        });

        response_json(json_success([
            'method' => 'PandaDB::transaction(callback)',
            'note'   => '回调内任意操作失败将自动回滚',
            'result' => $result,
        ], '事务执行成功'));
    } catch (\Throwable $e) {
        response_json(json_error('事务执行失败（已自动回滚）: ' . $e->getMessage(), 500));
    }
});

// --------------------------------------------------------------------------
// 3.7 PandaDB 数据库操作 - WHERE 高级条件
// --------------------------------------------------------------------------

/**
 * WHERE 高级条件：whereIn / whereBetween / whereLike / whereNull / whereNotNull / whereOr
 * 展示各种高级查询条件的用法
 *
 * GET /demo/db/advanced-where
 */
Route::get('/demo/db/advanced-where', function () {
    try {
        // whereIn：查询 ID 在指定范围内的记录
        $whereInResult = PandaDB::table('users')
            ->field('id, username')
            ->whereIn('id', [1, 2, 3])
            ->select();

        // whereBetween：查询 ID 在某个区间内的记录
        $whereBetweenResult = PandaDB::table('users')
            ->field('id, username')
            ->whereBetween('id', 1, 10)
            ->select();

        // whereLike：模糊查询
        $whereLikeResult = PandaDB::table('users')
            ->field('id, username')
            ->whereLike('username', 'admin', 'both')
            ->select();

        // whereNull：查询字段为 NULL 的记录
        $whereNullResult = PandaDB::table('users')
            ->field('id, username')
            ->whereNull('deleted_at')
            ->select();

        // whereNotNull：查询字段不为 NULL 的记录
        $whereNotNullResult = PandaDB::table('users')
            ->field('id, username, email')
            ->whereNotNull('email')
            ->limit(5)
            ->select();

        // whereOr：OR 条件查询
        $whereOrResult = PandaDB::table('users')
            ->field('id, username')
            ->where('status', 1)
            ->whereOr('username', 'admin')
            ->limit(10)
            ->select();

        response_json(json_success([
            'method' => 'whereIn / whereBetween / whereLike / whereNull / whereNotNull / whereOr',
            'results' => [
                'whereIn'      => ['count' => count($whereInResult), 'data' => $whereInResult],
                'whereBetween' => ['count' => count($whereBetweenResult), 'data' => $whereBetweenResult],
                'whereLike'    => ['count' => count($whereLikeResult), 'data' => $whereLikeResult],
                'whereNull'    => ['count' => count($whereNullResult), 'data' => $whereNullResult],
                'whereNotNull' => ['count' => count($whereNotNullResult), 'data' => $whereNotNullResult],
                'whereOr'      => ['count' => count($whereOrResult), 'data' => $whereOrResult],
            ],
        ]));
    } catch (\Throwable $e) {
        response_json(json_error('高级条件查询失败: ' . $e->getMessage(), 500));
    }
});

// --------------------------------------------------------------------------
// 3.8 PandaDB 数据库操作 - 慢查询统计和 SQL 日志
// --------------------------------------------------------------------------

/**
 * 慢查询统计
 * 展示当前请求中所有超过阈值的 SQL 查询
 *
 * GET /demo/db/slow-queries
 */
Route::get('/demo/db/slow-queries', function () {
    try {
        // 先执行一些查询以产生日志
        PandaDB::table('users')->limit(10)->select();
        PandaDB::table('users')->where('status', 1)->count();

        $slowQueries = PandaDB::getSlowQueries();
        $queryCount  = PandaDB::getQueryCount();
        $totalTime   = PandaDB::getTotalQueryTime();
        $lastSql     = PandaDB::getLastSql();
        $queryLog    = PandaDB::getQueryLog();

        response_json(json_success([
            'method'         => 'PandaDB::getSlowQueries() / getQueryCount() / getTotalQueryTime()',
            'slow_threshold' => '100ms',
            'statistics'     => [
                'total_queries'     => $queryCount,
                'total_time_ms'     => round($totalTime, 2),
                'slow_query_count'  => count($slowQueries),
                'last_sql'          => $lastSql,
            ],
            'slow_queries'   => $slowQueries,
            'query_log'      => $queryLog,
        ]));
    } catch (\Throwable $e) {
        response_json(json_error('获取慢查询日志失败: ' . $e->getMessage(), 500));
    }
});

/**
 * SQL 日志查看
 * 查看当前请求中执行的所有 SQL 语句
 *
 * GET /demo/db/sql-log
 */
Route::get('/demo/db/sql-log', function () {
    try {
        // 执行几条查询以产生日志
        PandaDB::table('users')->field('id, username')->limit(3)->select();
        PandaDB::table('users')->where('status', 1)->count();
        PandaDB::table('users')->where('id', 1)->find();

        $queryLog  = PandaDB::getQueryLog();
        $lastSql   = PandaDB::getLastSql();

        response_json(json_success([
            'method'      => 'PandaDB::getQueryLog() / getLastSql()',
            'last_sql'    => $lastSql,
            'total_count' => count($queryLog),
            'log'         => array_map(function ($item) {
                return [
                    'sql'       => $item['sql'],
                    'bindings'  => $item['bindings'],
                    'time_ms'   => round($item['time'], 2),
                    'is_slow'   => $item['time'] > 100 ? 'YES' : 'no',
                ];
            }, $queryLog),
        ]));
    } catch (\Throwable $e) {
        response_json(json_error('获取 SQL 日志失败: ' . $e->getMessage(), 500));
    }
});

// --------------------------------------------------------------------------
// 3.9 缓存系统 - 基础操作
// --------------------------------------------------------------------------

/**
 * 缓存设置：set
 * 将数据存入缓存，支持设置过期时间（秒）
 *
 * POST /demo/cache/set
 * Body: key=demo_key&value=demo_value&ttl=300
 */
Route::post('/demo/cache/set', function () {
    $key   = get_input('key', 'demo_key');
    $value = get_input('value', 'demo_value');
    $ttl   = (int) get_input('ttl', 300);

    $result = Cache::set($key, $value, $ttl);

    response_json(json_success([
        'method'  => 'Cache::set(key, value, ttl)',
        'key'     => $key,
        'value'   => $value,
        'ttl'     => $ttl,
        'result'  => $result,
    ], '缓存设置成功'));
});

/**
 * 缓存读取：get
 * 从缓存中获取数据，支持默认值
 *
 * GET /demo/cache/get?key=demo_key
 */
Route::get('/demo/cache/get', function () {
    $key = get_input('key', 'demo_key');

    $value = Cache::get($key, 'default_value');

    response_json(json_success([
        'method' => 'Cache::get(key, default)',
        'key'    => $key,
        'value'  => $value,
    ]));
});

/**
 * 缓存检测：has
 * 判断缓存键是否存在
 *
 * GET /demo/cache/has?key=demo_key
 */
Route::get('/demo/cache/has', function () {
    $key  = get_input('key', 'demo_key');
    $exists = Cache::has($key);

    response_json(json_success([
        'method' => 'Cache::has(key)',
        'key'    => $key,
        'exists' => $exists,
    ]));
});

/**
 * 缓存删除：delete / forget
 * 两个方法功能相同，forget 是 delete 的别名
 *
 * POST /demo/cache/delete
 * Body: key=demo_key
 */
Route::post('/demo/cache/delete', function () {
    $key = get_input('key', 'demo_key');

    $result = Cache::delete($key);

    response_json(json_success([
        'method' => 'Cache::delete(key) / Cache::forget(key)',
        'key'    => $key,
        'result' => $result,
    ], '缓存已删除'));
});

// --------------------------------------------------------------------------
// 3.10 缓存系统 - remember 缓存穿透回源
// --------------------------------------------------------------------------

/**
 * remember 缓存穿透回源
 * 缓存不存在时自动执行回调获取数据并写入缓存
 * 典型场景：热点数据自动回源
 *
 * GET /demo/cache/remember?key=expensive_query
 */
Route::get('/demo/cache/remember', function () {
    $key = get_input('key', 'expensive_query');

    $data = Cache::remember($key, function () {
        // 模拟耗时查询（缓存不存在时执行）
        return [
            'source'    => 'database',
            'timestamp' => date('Y-m-d H:i:s'),
            'items'     => ['item1', 'item2', 'item3'],
        ];
    }, 60); // 缓存 60 秒

    response_json(json_success([
        'method' => 'Cache::remember(key, callback, ttl)',
        'note'   => '缓存不存在时自动执行回调并写入缓存，下次直接读取缓存',
        'key'    => $key,
        'data'   => $data,
    ]));
});

// --------------------------------------------------------------------------
// 3.11 缓存系统 - increment/decrement 原子操作
// --------------------------------------------------------------------------

/**
 * increment 原子递增
 * 适用于计数器、点赞数、访问量等场景
 *
 * POST /demo/cache/increment
 * Body: key=counter&step=1
 */
Route::post('/demo/cache/increment', function () {
    $key  = get_input('key', 'counter');
    $step = (int) get_input('step', 1);

    // 初始化计数器（如果不存在）
    if (!Cache::has($key)) {
        Cache::set($key, 0);
    }

    $newValue = Cache::increment($key, $step);

    response_json(json_success([
        'method'    => 'Cache::increment(key, step)',
        'note'      => '原子操作，线程安全，适用于高并发计数场景',
        'key'       => $key,
        'step'      => $step,
        'new_value' => $newValue,
    ]));
});

/**
 * decrement 原子递减
 *
 * POST /demo/cache/decrement
 * Body: key=counter&step=1
 */
Route::post('/demo/cache/decrement', function () {
    $key  = get_input('key', 'counter');
    $step = (int) get_input('step', 1);

    $newValue = Cache::decrement($key, $step);

    response_json(json_success([
        'method'    => 'Cache::decrement(key, step)',
        'key'       => $key,
        'step'      => $step,
        'new_value' => $newValue,
    ]));
});

// --------------------------------------------------------------------------
// 3.12 缓存系统 - 批量操作
// --------------------------------------------------------------------------

/**
 * 批量操作：setMultiple / getMultiple / deleteMultiple
 * 一次操作多个缓存键，减少网络开销
 *
 * POST /demo/cache/batch
 */
Route::post('/demo/cache/batch', function () {
    // 批量设置
    $values = [
        'batch_key_1' => 'value_1',
        'batch_key_2' => 'value_2',
        'batch_key_3' => 'value_3',
    ];
    Cache::setMultiple($values, 300);

    // 批量获取
    $results = Cache::getMultiple(['batch_key_1', 'batch_key_2', 'batch_key_3', 'nonexistent'], 'default');

    // 批量删除
    Cache::deleteMultiple(['batch_key_1', 'batch_key_2', 'batch_key_3']);

    response_json(json_success([
        'method'  => 'Cache::setMultiple() / getMultiple() / deleteMultiple()',
        'note'    => '批量操作减少 I/O 次数，提升性能',
        'set'     => $values,
        'get'     => $results,
        'deleted' => ['batch_key_1', 'batch_key_2', 'batch_key_3'],
    ]));
});

// --------------------------------------------------------------------------
// 3.13 缓存系统 - tag 标签缓存
// --------------------------------------------------------------------------

/**
 * tag 标签缓存
 * 按标签分组管理缓存，支持按标签批量清除
 * 典型场景：按模块清除缓存（如清除所有用户相关缓存）
 *
 * POST /demo/cache/tag
 */
Route::post('/demo/cache/tag', function () {
    // 设置带标签的缓存
    Cache::tag('user')->set('user:1:profile', ['name' => 'Alice', 'age' => 25]);
    Cache::tag('user')->set('user:1:settings', ['theme' => 'dark', 'lang' => 'zh']);
    Cache::tag('user')->set('user:2:profile', ['name' => 'Bob', 'age' => 30]);

    // 读取标签缓存
    $profile  = Cache::tag('user')->get('user:1:profile');
    $settings = Cache::tag('user')->get('user:1:settings');

    // 按标签批量清除
    Cache::flush('user');

    // 验证清除结果
    $afterFlush = Cache::tag('user')->get('user:1:profile');

    response_json(json_success([
        'method'      => 'Cache::tag(name)->set/get/flush()',
        'note'        => '按标签分组管理缓存，flush("user") 清除所有 user 标签缓存',
        'before_flush' => [
            'user:1:profile'  => $profile,
            'user:1:settings' => $settings,
        ],
        'after_flush'  => [
            'user:1:profile' => $afterFlush, // 应为 null
        ],
    ]));
});

// --------------------------------------------------------------------------
// 3.14 验证器
// --------------------------------------------------------------------------

/**
 * Validator 验证器演示
 * 支持 required/email/min/max/numeric/between/confirmed 等规则
 * 使用管道符 | 分隔多条规则
 *
 * POST /demo/validator
 * Body: username=test&email=test@test.com&age=25&password=123456&password_confirmation=123456
 */
Route::post('/demo/validator', function () {
    $data = [
        'username'              => get_input('username', ''),
        'email'                 => get_input('email', ''),
        'age'                   => get_input('age', ''),
        'password'              => get_input('password', ''),
        'password_confirmation' => get_input('password_confirmation', ''),
    ];

    $validator = new Validator($data, [
        'username'              => 'required|min:2|max:20',
        'email'                 => 'required|email',
        'age'                   => 'required|numeric|between:1,150',
        'password'              => 'required|min:6|max:32',
        'password_confirmation' => 'required|confirmed',
    ], [
        'username.required' => '用户名不能为空',
        'username.min'      => '用户名至少2个字符',
        'email.email'       => '请输入有效的邮箱地址',
        'age.between'       => '年龄必须在1到150之间',
        'password.min'      => '密码至少6个字符',
        'password_confirmation.confirmed' => '两次密码输入不一致',
    ]);

    if ($validator->fails()) {
        response_json(json_error('验证失败', 422, [
            'errors' => $validator->errors(),
            'first'  => $validator->firstError(),
        ]), 422);
    }

    response_json(json_success([
        'method'    => 'new Validator(data, rules, messages)',
        'note'      => '管道符 | 分隔规则，支持自定义错误消息',
        'validated' => $validator->validated(),
    ], '验证通过'));
});

// --------------------------------------------------------------------------
// 3.15 中间件 - CORS 跨域
// --------------------------------------------------------------------------

/**
 * CORS 跨域中间件演示
 * 通过路由组应用 CORS 中间件，允许跨域请求
 *
 * GET /demo/middleware/cors
 */
Route::group([
    'middleware' => [CorsMiddleware::class],
], function () {
    Route::get('/demo/middleware/cors', function () {
        response_json(json_success([
            'method' => 'CorsMiddleware',
            'note'   => '此路由已应用 CORS 中间件，允许跨域访问',
            'headers' => [
                'Access-Control-Allow-Origin'  => '*',
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
            ],
        ]));
    });
});

// --------------------------------------------------------------------------
// 3.16 中间件 - Auth 认证
// --------------------------------------------------------------------------

/**
 * Auth 认证中间件演示
 * 需要在请求头中携带 Authorization: Bearer <token>
 *
 * GET /demo/middleware/auth
 * Header: Authorization: Bearer your_token_here
 */
Route::get('/demo/middleware/auth', function () {
    $token = get_header('Authorization', '');
    $token = str_replace('Bearer ', '', $token);

    if (empty($token) || strlen($token) < 10) {
        response_json(json_error('未授权：请在请求头中携带有效的 Authorization: Bearer <token>', 401), 401);
    }

    response_json(json_success([
        'method' => 'AuthMiddleware',
        'note'   => '需要 Authorization: Bearer <token> 请求头',
        'token'  => substr($token, 0, 10) . '...',
        'user'   => [
            'id'   => 1,
            'name' => 'Demo User',
        ],
    ], '认证成功'));
})->middleware(AuthMiddleware::class);

// --------------------------------------------------------------------------
// 3.17 中间件 - Throttle 限流
// --------------------------------------------------------------------------

/**
 * Throttle 限流中间件演示
 * 限制每个 IP 在指定时间内的请求次数
 *
 * GET /demo/middleware/throttle
 */
Route::get('/demo/middleware/throttle', function () {
    $ip = get_client_ip();

    response_json(json_success([
        'method' => 'ThrottleMiddleware',
        'note'   => '默认限制每分钟60次请求，超出返回 429 状态码',
        'client_ip' => $ip,
        'config' => [
            'max_attempts'  => 60,
            'decay_minutes' => 1,
        ],
    ]));
})->middleware(ThrottleMiddleware::class);

// --------------------------------------------------------------------------
// 3.18 公共函数演示
// --------------------------------------------------------------------------

/**
 * json_success / json_error / json_page 响应函数
 * 统一的 JSON 响应格式
 *
 * GET /demo/helper/json-response
 */
Route::get('/demo/helper/json-response', function () {
    response_json([
        'code'    => 200,
        'message' => '公共函数演示',
        'data'    => [
            'json_success' => json_success(['id' => 1], '操作成功'),
            'json_error'   => json_error('参数错误', 400),
            'json_page'    => json_page(
                [['id' => 1], ['id' => 2]],
                100,
                1,
                10
            ),
        ],
    ]);
});

/**
 * get_input 获取参数
 * 自动合并 GET / POST / JSON Body 参数
 *
 * POST /demo/helper/get-input
 * Body: name=world
 */
Route::post('/demo/helper/get-input', function () {
    // 获取单个参数
    $name = get_input('name', 'default');

    // 获取所有参数
    $all = get_input();

    response_json(json_success([
        'method' => 'get_input(key, default) / get_input()',
        'note'   => '自动合并 GET/POST/JSON Body 三种来源的参数',
        'name'   => $name,
        'all'    => $all,
    ]));
});

/**
 * generate_token 生成 Token
 * 使用 random_bytes 生成安全的随机 Token
 *
 * GET /demo/helper/generate-token
 */
Route::get('/demo/helper/generate-token', function () {
    $token32 = generate_token(32);
    $token64 = generate_token(64);

    response_json(json_success([
        'method' => 'generate_token(length)',
        'note'   => '基于 random_bytes() 的安全随机 Token',
        'tokens' => [
            '32_chars' => $token32,
            '64_chars' => $token64,
        ],
    ]));
});

/**
 * password_encrypt / password_check 密码加密与验证
 * 使用 PHP 内置 password_hash / password_verify
 *
 * POST /demo/helper/password
 * Body: password=my_secret
 */
Route::post('/demo/helper/password', function () {
    $password = get_input('password', 'my_secret');

    // 加密
    $hash = password_encrypt($password);

    // 验证
    $isCorrect = password_check($password, $hash);
    $isWrong   = password_check('wrong_password', $hash);

    response_json(json_success([
        'method'     => 'password_encrypt(password) / password_check(password, hash)',
        'note'       => '使用 PASSWORD_DEFAULT (bcrypt) 算法，自动加盐',
        'password'   => $password,
        'hash'       => $hash,
        'verify_ok'  => $isCorrect,   // true
        'verify_fail' => $isWrong,    // false
    ]));
});

/**
 * get_client_ip 获取客户端 IP
 * 支持 X-Forwarded-For / X-Real-IP / REMOTE_ADDR
 *
 * GET /demo/helper/client-ip
 */
Route::get('/demo/helper/client-ip', function () {
    $ip = get_client_ip();

    response_json(json_success([
        'method' => 'get_client_ip()',
        'note'   => '支持代理场景，依次检查 X-Forwarded-For / X-Real-IP / REMOTE_ADDR',
        'ip'     => $ip,
    ]));
});

/**
 * format_date 格式化日期
 * 支持时间戳、日期字符串、当前时间
 *
 * GET /demo/helper/format-date
 */
Route::get('/demo/helper/format-date', function () {
    $now        = format_date();                        // 当前时间
    $timestamp  = format_date(time(), 'Y-m-d');         // 时间戳
    $stringDate = format_date('2024-01-15', 'Y年m月d日'); // 日期字符串
    $custom     = format_date(null, 'Y/m/d H:i');       // 自定义格式

    response_json(json_success([
        'method' => 'format_date(datetime, format)',
        'results' => [
            'now'              => $now,
            'from_timestamp'   => $timestamp,
            'from_string'      => $stringDate,
            'custom_format'    => $custom,
        ],
    ]));
});

/**
 * dd 调试函数
 * 打印变量并以 JSON 格式输出后终止程序
 * 注意：此接口会直接输出调试信息并终止
 *
 * GET /demo/helper/dd
 */
Route::get('/demo/helper/dd', function () {
    dd([
        'method' => 'dd(...$vars)',
        'note'   => '调试函数，输出变量后终止程序',
        'data'   => [
            'key1' => 'value1',
            'key2' => ['nested' => true],
        ],
    ]);
});

/**
 * response_json 统一 JSON 输出
 * 设置 HTTP 状态码和 Content-Type 后输出 JSON 并终止
 *
 * GET /demo/helper/response-json
 */
Route::get('/demo/helper/response-json', function () {
    // 此函数会直接输出并 exit
    response_json(json_success([
        'method' => 'response_json(data, httpCode)',
        'note'   => '统一 JSON 输出，自动设置 Content-Type 和 HTTP 状态码',
    ]), 200);
});

// --------------------------------------------------------------------------
// 3.19 无状态设计说明
// --------------------------------------------------------------------------

/**
 * 无状态设计说明
 * PandaAPI 采用无状态架构，不依赖 Session/Cookie
 *
 * GET /demo/stateless
 */
Route::get('/demo/stateless', function () {
    response_json(json_success([
        'title'  => 'PandaAPI 无状态设计',
        'concept' => [
            'description'   => '服务端不存储用户会话状态，每次请求都携带完整的认证信息',
            'authentication' => '基于 Token（JWT / 自定义 Token），通过 Authorization 请求头传递',
            'cache'          => '使用 Redis/File/Memcache 替代 Session，支持分布式部署',
            'scalability'    => '任意节点均可处理请求，天然支持水平扩展和负载均衡',
        ],
        'vs_traditional' => [
            'traditional' => 'Session 存储在服务端，需要 Sticky Session 或 Session 共享',
            'pandaapi'    => 'Token 携带在请求中，任意节点均可验证，无需共享状态',
        ],
        'best_practices' => [
            '1. 使用 Token 认证，避免 Session 依赖',
            '2. 敏感数据存缓存（Redis），设置合理的 TTL',
            '3. 限流基于 IP + Token 双维度',
            '4. API 响应包含完整的业务状态，不依赖上下文',
        ],
    ]));
});

// --------------------------------------------------------------------------
// 3.20 路由列表
// --------------------------------------------------------------------------

/**
 * 获取所有 Demo 路由列表
 * 方便前端开发者查阅可用接口
 *
 * GET /demo/routes
 */
Route::get('/demo/routes', function () {
    $routes = [
        // 框架信息
        ['method' => 'GET',    'path' => '/demo/info',              'description' => '框架信息总览'],
        ['method' => 'GET',    'path' => '/demo/health',            'description' => '健康检查'],
        ['method' => 'GET',    'path' => '/demo/stateless',         'description' => '无状态设计说明'],
        ['method' => 'GET',    'path' => '/demo/routes',            'description' => '路由列表'],

        // 数据库 - ThinkPHP 风格
        ['method' => 'GET',    'path' => '/demo/db/chain-select',   'description' => '链式查询 select'],
        ['method' => 'GET',    'path' => '/demo/db/chain-find',     'description' => '链式查询 find'],
        ['method' => 'GET',    'path' => '/demo/db/name-prefix',    'description' => 'name() 表前缀'],

        // 数据库 - PandaDB 快捷方法
        ['method' => 'GET',    'path' => '/demo/db/medoo-select',   'description' => 'PandaDB select'],
        ['method' => 'GET',    'path' => '/demo/db/medoo-get',      'description' => 'PandaDB get'],
        ['method' => 'POST',   'path' => '/demo/db/medoo-insert',   'description' => 'PandaDB insert'],
        ['method' => 'POST',   'path' => '/demo/db/medoo-update',   'description' => 'PandaDB update'],
        ['method' => 'POST',   'path' => '/demo/db/medoo-delete',   'description' => 'PandaDB delete'],
        ['method' => 'GET',    'path' => '/demo/db/medoo-aggregate','description' => '聚合函数 count/sum/avg/max/min'],

        // 数据库 - 高级功能
        ['method' => 'GET',    'path' => '/demo/db/join',           'description' => 'JOIN 关联查询'],
        ['method' => 'GET',    'path' => '/demo/db/paginate',       'description' => '分页查询'],
        ['method' => 'GET',    'path' => '/demo/db/raw-query',      'description' => '原生 SQL 查询'],
        ['method' => 'POST',   'path' => '/demo/db/raw-execute',    'description' => '原生 SQL 执行'],
        ['method' => 'POST',   'path' => '/demo/db/transaction',    'description' => '事务操作'],
        ['method' => 'GET',    'path' => '/demo/db/advanced-where', 'description' => 'WHERE 高级条件'],
        ['method' => 'GET',    'path' => '/demo/db/slow-queries',   'description' => '慢查询统计'],
        ['method' => 'GET',    'path' => '/demo/db/sql-log',        'description' => 'SQL 日志'],

        // 缓存
        ['method' => 'POST',   'path' => '/demo/cache/set',         'description' => '缓存设置 set'],
        ['method' => 'GET',    'path' => '/demo/cache/get',         'description' => '缓存读取 get'],
        ['method' => 'GET',    'path' => '/demo/cache/has',         'description' => '缓存检测 has'],
        ['method' => 'POST',   'path' => '/demo/cache/delete',      'description' => '缓存删除 delete/forget'],
        ['method' => 'GET',    'path' => '/demo/cache/remember',    'description' => '缓存回源 remember'],
        ['method' => 'POST',   'path' => '/demo/cache/increment',   'description' => '原子递增 increment'],
        ['method' => 'POST',   'path' => '/demo/cache/decrement',   'description' => '原子递减 decrement'],
        ['method' => 'POST',   'path' => '/demo/cache/batch',       'description' => '批量操作'],
        ['method' => 'POST',   'path' => '/demo/cache/tag',         'description' => '标签缓存'],

        // 验证器
        ['method' => 'POST',   'path' => '/demo/validator',         'description' => '验证器演示'],

        // 中间件
        ['method' => 'GET',    'path' => '/demo/middleware/cors',    'description' => 'CORS 跨域'],
        ['method' => 'GET',    'path' => '/demo/middleware/auth',    'description' => 'Auth 认证'],
        ['method' => 'GET',    'path' => '/demo/middleware/throttle','description' => 'Throttle 限流'],

        // 公共函数
        ['method' => 'GET',    'path' => '/demo/helper/json-response',   'description' => 'JSON 响应函数'],
        ['method' => 'POST',   'path' => '/demo/helper/get-input',       'description' => '获取参数 get_input'],
        ['method' => 'GET',    'path' => '/demo/helper/generate-token',  'description' => '生成 Token'],
        ['method' => 'POST',   'path' => '/demo/helper/password',        'description' => '密码加密验证'],
        ['method' => 'GET',    'path' => '/demo/helper/client-ip',       'description' => '获取客户端 IP'],
        ['method' => 'GET',    'path' => '/demo/helper/format-date',     'description' => '格式化日期'],
        ['method' => 'GET',    'path' => '/demo/helper/dd',              'description' => '调试 dd'],
        ['method' => 'GET',    'path' => '/demo/helper/response-json',   'description' => '统一 JSON 输出'],
    ];

    response_json(json_success([
        'total'  => count($routes),
        'routes' => $routes,
    ]));
});

// ============================================================================
// 第四部分：路由分发
// ============================================================================

/**
 * 路由分发入口
 * 解析当前请求的 URI 和 Method，匹配对应的路由处理器
 */
$uri    = strtok($_SERVER['REQUEST_URI'], '?');
$method = $_SERVER['REQUEST_METHOD'];

try {
    $result = Route::dispatch($method, $uri);

    // 如果返回的是数组，自动转为 JSON 输出
    if (is_array($result)) {
        response_json($result);
    }

    // 如果返回的是字符串，直接输出
    if (is_string($result)) {
        echo $result;
    }
} catch (\PandaAPI\Exception\ApiException $e) {
    response_json(json_error($e->getMessage(), $e->getCode()), $e->getCode());
} catch (\Throwable $e) {
    response_json(json_error('服务器内部错误: ' . $e->getMessage(), 500), 500);
}
