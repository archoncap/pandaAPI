<?php
/**
 * 熊猫API框架 (PandaAPI) 完整演示项目
 * 
 * 本Demo展示了框架的所有功能使用方法
 * 包含：PandaDB数据库操作、缓存系统、路由、中间件、验证器等
 * 
 * @author PandaAPI Team
 * @version 1.0.0
 */

// ============================================================
// 1. 引入必要的类
// ============================================================
require_once __DIR__ . '/../vendor/autoload.php';

use PandaAPI\Core\App;
use PandaAPI\Core\Request;
use PandaAPI\Core\Response;
use PandaAPI\Database\PandaDB;
use PandaAPI\Route\Route;
use PandaAPI\Cache\Cache;
use PandaAPI\Validation\Validator;

// ============================================================
// 2. 应用配置
// ============================================================

// 配置数据库连接（使用 PandaDB）
PandaDB::config([
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => 'pandaapi_demo',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'prefix' => 'panda_',
]);

// 配置缓存（支持：file/redis/memcache/openresty）
Cache::config([
    'driver' => 'file',
    'file' => [
        'path' => __DIR__ . '/../runtime/cache',
    ],
    'default_ttl' => 3600,
]);

// 设置慢查询阈值（毫秒）
PandaDB::setSlowThreshold(100);

// ============================================================
// 3. 创建演示用数据表
// ============================================================

/**
 * 初始化数据库表结构
 * 这些SQL用于演示，实际项目中通常通过迁移工具管理
 */
function initDatabase()
{
    // 创建用户表
    $createUsersTable = "
        CREATE TABLE IF NOT EXISTS panda_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            status TINYINT DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    // 创建文章表
    $createPostsTable = "
        CREATE TABLE IF NOT EXISTS panda_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            content TEXT,
            views INT DEFAULT 0,
            status TINYINT DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES panda_users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    // 创建分类表
    $createCategoriesTable = "
        CREATE TABLE IF NOT EXISTS panda_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            slug VARCHAR(50) NOT NULL UNIQUE,
            parent_id INT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    try {
        PandaDB::execute($createUsersTable);
        PandaDB::execute($createPostsTable);
        PandaDB::execute($createCategoriesTable);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// ============================================================
// 4. 定义API路由
// ============================================================

/**
 * 健康检查接口
 * GET /api/v1/health
 */
Route::get('/api/v1/health', function() {
    return Response::success([
        'framework' => 'PandaAPI',
        'name' => '熊猫API框架',
        'status' => 'healthy',
        'time' => date('Y-m-d H:i:s'),
        'version' => '1.0.0',
        'db_queries' => PandaDB::getQueryCount(),
        'uptime' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
    ]);
});

/**
 * API版本组 v1
 */
Route::group(['prefix' => '/api/v1', 'middleware' => ['Cors']], function() {
    
    // ----------------------------------------
    // 用户相关接口 (公开)
    // ----------------------------------------
    
    /**
     * 获取用户列表
     * GET /api/v1/users
     * 
     * 支持参数:
     * - page: 页码 (默认1)
     * - per_page: 每页数量 (默认15)
     * - status: 用户状态筛选
     */
    Route::get('/users', function() {
        $request = new Request();
        $page = (int)$request->query('page', 1);
        $perPage = (int)$request->query('per_page', 15);
        $status = $request->query('status');
        
        // 使用 PandaDB 构建查询
        $builder = PandaDB::table('users');
        
        // 条件筛选
        if ($status !== null) {
            $builder->where('status', (int)$status);
        }
        
        // 排序
        $builder->order('created_at', 'desc');
        
        // 分页查询
        $result = $builder->paginate($perPage, $page);
        
        return Response::paginate(
            $result['data'],
            $result['total'],
            $result['current_page'],
            $result['per_page']
        );
    });
    
    /**
     * 获取单个用户
     * GET /api/v1/users/{id}
     * 
     * 使用缓存示例：先查缓存，缓存不存在则查数据库
     */
    Route::get('/users/{id}', function($id) {
        // 尝试从缓存获取
        $cacheKey = "user:{$id}";
        
        $user = Cache::remember($cacheKey, function() use ($id) {
            return PandaDB::table('users')
                ->where('id', (int)$id)
                ->find();
        }, 300); // 缓存5分钟
        
        if (!$user) {
            return Response::error('用户不存在', 404);
        }
        
        return Response::success($user);
    })->whereNumber('id');
    
    /**
     * 创建用户
     * POST /api/v1/users
     * 
     * 请求体:
     * - name: 用户名 (必填)
     * - email: 邮箱 (必填)
     * - password: 密码 (必填，最小6位)
     */
    Route::post('/users', function() {
        $request = new Request();
        $data = $request->all();
        
        // 数据验证
        $validator = new Validator($data, [
            'name' => 'required|string|min:2|max:50',
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);
        
        if ($validator->fails()) {
            return Response::json([
                'error' => '验证失败',
                'messages' => $validator->errors()
            ], 422);
        }
        
        // 检查邮箱唯一性
        $exists = PandaDB::table('users')
            ->where('email', $data['email'])
            ->count();
        
        if ($exists > 0) {
            return Response::error('邮箱已被注册', 422);
        }
        
        // 密码加密
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['created_at'] = date('Y-m-d H:i:s');
        
        // 插入数据（使用 PandaDB 的 insert 方法）
        $id = PandaDB::table('users')->insert($data);
        
        // 获取创建的用户
        $user = PandaDB::table('users')->where('id', $id)->find();
        unset($user['password']); // 移除密码字段
        
        return Response::json($user, 201);
    });
    
    /**
     * 更新用户
     * PUT /api/v1/users/{id}
     */
    Route::put('/users/{id}', function($id) {
        $request = new Request();
        $data = $request->all();
        
        // 验证
        $validator = new Validator($data, [
            'name' => 'string|min:2|max:50',
            'email' => 'email',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        // 检查用户是否存在
        $user = PandaDB::table('users')->where('id', (int)$id)->find();
        if (!$user) {
            return Response::error('用户不存在', 404);
        }
        
        // 更新数据
        if (!empty($data)) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            PandaDB::table('users')->where('id', (int)$id)->update($data);
            
            // 清除用户缓存
            Cache::forget("user:{$id}");
        }
        
        // 返回更新后的用户
        $user = PandaDB::table('users')->where('id', (int)$id)->find();
        unset($user['password']);
        
        return Response::success($user);
    })->whereNumber('id');
    
    /**
     * 删除用户
     * DELETE /api/v1/users/{id}
     */
    Route::delete('/users/{id}', function($id) {
        $user = PandaDB::table('users')->where('id', (int)$id)->find();
        
        if (!$user) {
            return Response::error('用户不存在', 404);
        }
        
        PandaDB::table('users')->where('id', (int)$id)->delete();
        
        // 清除缓存
        Cache::forget("user:{$id}");
        
        return Response::success(['message' => '删除成功']);
    })->whereNumber('id');
    
    // ----------------------------------------
    // 文章相关接口 (公开)
    // ----------------------------------------
    
    /**
     * 获取文章列表
     * GET /api/v1/posts
     */
    Route::get('/posts', function() {
        $request = new Request();
        $page = (int)$request->query('page', 1);
        $perPage = (int)$request->query('per_page', 10);
        
        // JOIN查询示例：关联用户表
        $result = PandaDB::table('posts')
            ->field('posts.*, users.name as author_name')
            ->join('users', 'posts.user_id = users.id')
            ->where('posts.status', 1)
            ->order('posts.created_at', 'desc')
            ->paginate($perPage, $page);
        
        return Response::paginate(
            $result['data'],
            $result['total'],
            $result['current_page'],
            $result['per_page']
        );
    });
    
    /**
     * 获取文章详情
     * GET /api/v1/posts/{id}
     */
    Route::get('/posts/{id}', function($id) {
        // 增加浏览量
        $post = PandaDB::table('posts')->where('id', (int)$id)->find();
        if ($post) {
            PandaDB::table('posts')
                ->where('id', (int)$id)
                ->update(['views' => $post['views'] + 1]);
        }
        
        // 关联查询获取文章和作者信息
        $post = PandaDB::table('posts')
            ->field('posts.*, users.name as author_name, users.email as author_email')
            ->join('users', 'posts.user_id = users.id')
            ->where('posts.id', (int)$id)
            ->find();
        
        if (!$post) {
            return Response::error('文章不存在', 404);
        }
        
        return Response::success($post);
    })->whereNumber('id');
    
    /**
     * 创建文章
     * POST /api/v1/posts
     */
    Route::post('/posts', function() {
        $request = new Request();
        $data = $request->all();
        
        // 验证
        $validator = new Validator($data, [
            'title' => 'required|string|min:2|max:200',
            'content' => 'required|string|min:10',
            'user_id' => 'required|integer',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['status'] = 1;
        
        $id = PandaDB::table('posts')->insert($data);
        
        return Response::json(['id' => $id, 'message' => '创建成功'], 201);
    });
    
    // ----------------------------------------
    // 聚合查询示例
    // ----------------------------------------
    
    /**
     * 统计接口
     * GET /api/v1/stats
     */
    Route::get('/stats', function() {
        $stats = [
            'users_count' => PandaDB::table('users')->count(),
            'posts_count' => PandaDB::table('posts')->count(),
            'posts_views_sum' => PandaDB::table('posts')->sum('views'),
            'posts_avg_views' => round(PandaDB::table('posts')->avg('views'), 2),
            'active_users' => PandaDB::table('users')->where('status', 1)->count(),
        ];
        
        return Response::success($stats);
    });
    
    // ----------------------------------------
    // 缓存操作示例
    // ----------------------------------------
    
    /**
     * 缓存测试接口
     * GET /api/v1/cache/test
     */
    Route::get('/cache/test', function() {
        $key = 'test:counter';
        
        // 增加值
        if (!Cache::has($key)) {
            Cache::set($key, 0, 3600);
        }
        $count = Cache::increment($key);
        
        // 获取多个缓存
        Cache::setMultiple([
            'cache:item1' => 'value1',
            'cache:item2' => 'value2',
            'cache:item3' => ['array' => 'data'],
        ], 600);
        
        $items = Cache::getMultiple(['cache:item1', 'cache:item2', 'cache:item3']);
        
        return Response::success([
            'counter' => $count,
            'items' => $items,
        ]);
    });
    
    /**
     * 清除所有缓存
     * DELETE /api/v1/cache
     */
    Route::delete('/cache', function() {
        Cache::clear();
        return Response::success(['message' => '缓存已清空']);
    });
    
    /**
     * 使用标签管理缓存
     * GET /api/v1/cache/tagged
     */
    Route::get('/cache/tagged', function() {
        $tag = 'api_cache';
        
        // 设置带标签的缓存
        Cache::tag($tag)->set('user_list', PandaDB::table('users')->select());
        Cache::tag($tag)->set('post_list', PandaDB::table('posts')->select());
        
        return Response::success([
            'message' => '已设置标签缓存',
            'tag' => $tag,
        ]);
    });
    
    // ----------------------------------------
    // 原生SQL查询示例
    // ----------------------------------------
    
    /**
     * 原生SQL查询
     * GET /api/v1/sql
     */
    Route::get('/sql', function() {
        // 原生查询示例
        $users = PandaDB::query(
            "SELECT * FROM panda_users WHERE status = ? ORDER BY created_at DESC LIMIT 10",
            [1]
        );
        
        // 原生执行示例（用于统计等）
        $avgViews = PandaDB::query(
            "SELECT AVG(views) as avg_views FROM panda_posts WHERE status = 1"
        );
        
        return Response::success([
            'users' => $users,
            'avg_views' => $avgViews[0]['avg_views'] ?? 0,
        ]);
    });
    
    // ----------------------------------------
    // 事务示例
    // ----------------------------------------
    
    /**
     * 事务操作示例
     * POST /api/v1/transaction
     */
    Route::post('/transaction', function() {
        try {
            $result = PandaDB::transaction(function() {
                // 创建用户
                $userId = PandaDB::table('users')->insert([
                    'name' => '事务测试用户',
                    'email' => 'transaction_' . time() . '@test.com',
                    'password' => password_hash('123456', PASSWORD_DEFAULT),
                    'status' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                
                // 创建用户的默认文章
                PandaDB::table('posts')->insert([
                    'user_id' => $userId,
                    'title' => '欢迎文章',
                    'content' => '这是用户的欢迎文章',
                    'status' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                
                return ['user_id' => $userId];
            });
            
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error('事务失败: ' . $e->getMessage(), 500);
        }
    });
    
    // ----------------------------------------
    // PandaDB 风格快捷方法示例
    // ----------------------------------------
    
    /**
     * 使用 PandaDB 风格快捷方法
     * GET /api/v1/pandadb-demo
     */
    Route::get('/pandadb-demo', function() {
        // 使用 PandaDB 快捷方法
        $user = PandaDB::get('users', ['id', 'name', 'email'], ['id' => 1]);
        
        // 使用 ThinkPHP 风格的查询
        $users = PandaDB::table('users')
            ->where('status', 1)
            ->order('id', 'desc')
            ->limit(5)
            ->select();
        
        // 使用 name() 方法（自动添加表前缀）
        $count = PandaDB::name('users')->count();
        
        return Response::success([
            'single_user' => $user,
            'active_users' => $users,
            'total_count' => $count,
        ]);
    });
});

/**
 * 认证相关接口组
 * 需要认证的接口
 */
Route::group(['prefix' => '/api/v1/auth', 'middleware' => ['Cors']], function() {
    
    /**
     * 用户登录
     * POST /api/v1/auth/login
     */
    Route::post('/login', function() {
        $request = new Request();
        $data = $request->all();
        
        if (empty($data['email']) || empty($data['password'])) {
            return Response::error('邮箱和密码不能为空', 400);
        }
        
        // 查找用户
        $user = PandaDB::table('users')
            ->where('email', $data['email'])
            ->find();
        
        if (!$user) {
            return Response::error('邮箱或密码错误', 401);
        }
        
        // 验证密码
        if (!password_verify($data['password'], $user['password'])) {
            return Response::error('邮箱或密码错误', 401);
        }
        
        // 生成Token
        $token = bin2hex(random_bytes(32));
        
        // 存储Token到缓存（7天有效期）
        Cache::set("token:{$token}", [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'created_at' => time(),
        ], 86400 * 7);
        
        return Response::success([
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
            ],
        ]);
    });
    
    /**
     * 用户注册
     * POST /api/v1/auth/register
     */
    Route::post('/register', function() {
        $request = new Request();
        $data = $request->all();
        
        // 验证
        $validator = new Validator($data, [
            'name' => 'required|string|min:2|max:50',
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        // 检查邮箱唯一性
        if (PandaDB::table('users')->where('email', $data['email'])->count() > 0) {
            return Response::error('邮箱已被注册', 422);
        }
        
        // 创建用户
        $userId = PandaDB::table('users')->insert([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        
        // 生成Token
        $token = bin2hex(random_bytes(32));
        Cache::set("token:{$token}", [
            'user_id' => $userId,
            'email' => $data['email'],
            'created_at' => time(),
        ], 86400 * 7);
        
        return Response::json([
            'token' => $token,
            'user' => [
                'id' => $userId,
                'name' => $data['name'],
                'email' => $data['email'],
            ],
        ], 201);
    });
    
    /**
     * 获取当前用户信息
     * GET /api/v1/auth/me
     * 需要在请求头中添加 Authorization: Bearer {token}
     */
    Route::get('/me', function() {
        // 获取当前用户（由AuthMiddleware注入）
        $request = new Request();
        $user = $request->user();
        
        if (!$user) {
            return Response::error('未登录', 401);
        }
        
        return Response::success($user);
    });
});

/**
 * 需要认证的接口组
 */
Route::group(['prefix' => '/api/v1/private', 'middleware' => ['Cors', 'Auth']], function() {
    
    /**
     * 个人资料
     * GET /api/v1/private/profile
     */
    Route::get('/profile', function() {
        $request = new Request();
        $user = $request->user();
        
        return Response::success($user);
    });
    
    /**
     * 获取我的文章
     * GET /api/v1/private/my-posts
     */
    Route::get('/my-posts', function() {
        $request = new Request();
        $user = $request->user();
        
        $posts = PandaDB::table('posts')
            ->where('user_id', $user['id'])
            ->order('created_at', 'desc')
            ->select();
        
        return Response::success($posts);
    });
    
    /**
     * 性能统计（仅限调试模式）
     * GET /api/v1/private/debug
     */
    Route::get('/debug', function() {
        $stats = [
            'queries' => PandaDB::getQueryLog(),
            'slow_queries' => PandaDB::getSlowQueries(),
            'total_time' => PandaDB::getTotalQueryTime(),
            'query_count' => PandaDB::getQueryCount(),
        ];
        
        return Response::success($stats);
    });
});

/**
 * 404处理
 */
Route::set404(function($path) {
    return Response::error('接口不存在: ' . $path, 404);
});

// ============================================================
// 5. 运行应用
// ============================================================

// 创建应用实例
$app = new App();

// 配置应用
$app->config([
    'env' => 'development',
    'debug' => true,
    'timezone' => 'Asia/Shanghai',
    'middleware' => ['Cors', 'Log'],
]);

// 运行
$app->run();
