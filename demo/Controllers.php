<?php
/**
 * QuickAPI Demo - 控制器示例
 * 
 * 展示如何使用控制器组织代码
 * 
 * @author QuickAPI Team
 */

namespace Demo\Controller;

use QuickAPI\Core\Request;
use QuickAPI\Core\Response;
use QuickAPI\Database\DB;
use QuickAPI\Cache\Cache;
use QuickAPI\Validation\Validator;

/**
 * 用户控制器
 */
class UserController
{
    /**
     * 用户列表
     * GET /users
     */
    public function index(Request $request)
    {
        $page = (int)$request->query('page', 1);
        $perPage = (int)$request->query('per_page', 15);
        $status = $request->query('status');

        // 构建查询
        $builder = DB::table('users');

        if ($status !== null) {
            $builder->where('status', (int)$status);
        }

        $result = $builder->paginate($perPage, $page);

        return Response::paginate(
            $result['data'],
            $result['total'],
            $result['current_page'],
            $result['per_page']
        );
    }

    /**
     * 获取用户详情
     * GET /users/{id}
     */
    public function show(Request $request, $id)
    {
        $cacheKey = "user:{$id}";

        // 尝试从缓存获取
        $user = Cache::remember($cacheKey, function () use ($id) {
            return DB::table('users')
                ->where('id', (int)$id)
                ->find();
        }, 300);

        if (!$user) {
            return Response::error('用户不存在', 404);
        }

        // 移除密码
        unset($user['password']);

        return Response::success($user);
    }

    /**
     * 创建用户
     * POST /users
     */
    public function store(Request $request)
    {
        $data = $request->all();

        // 验证
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
        $exists = DB::table('users')
            ->where('email', $data['email'])
            ->count();

        if ($exists > 0) {
            return Response::error('邮箱已被注册', 422);
        }

        // 创建用户
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['status'] = 1;
        $data['created_at'] = date('Y-m-d H:i:s');

        $id = DB::table('users')->insert($data);

        // 获取创建的用户
        $user = DB::table('users')->where('id', $id)->find();
        unset($user['password']);

        return Response::json($user, 201);
    }

    /**
     * 更新用户
     * PUT /users/{id}
     */
    public function update(Request $request, $id)
    {
        $data = $request->all();

        // 检查用户是否存在
        $user = DB::table('users')->where('id', (int)$id)->find();

        if (!$user) {
            return Response::error('用户不存在', 404);
        }

        // 更新数据
        if (!empty($data)) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            DB::table('users')->where('id', (int)$id)->update($data);

            // 清除缓存
            Cache::forget("user:{$id}");
        }

        // 返回更新后的用户
        $user = DB::table('users')->where('id', (int)$id)->find();
        unset($user['password']);

        return Response::success($user);
    }

    /**
     * 删除用户
     * DELETE /users/{id}
     */
    public function destroy(Request $request, $id)
    {
        $user = DB::table('users')->where('id', (int)$id)->find();

        if (!$user) {
            return Response::error('用户不存在', 404);
        }

        DB::table('users')->where('id', (int)$id)->delete();

        // 清除缓存
        Cache::forget("user:{$id}");

        return Response::success(['message' => '删除成功']);
    }
}

/**
 * 文章控制器
 */
class PostController
{
    /**
     * 文章列表
     * GET /posts
     */
    public function index(Request $request)
    {
        $page = (int)$request->query('page', 1);
        $perPage = (int)$request->query('per_page', 10);

        $result = DB::table('posts')
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
    }

    /**
     * 文章详情
     * GET /posts/{id}
     */
    public function show(Request $request, $id)
    {
        // 增加浏览量
        DB::table('posts')
            ->where('id', (int)$id)
            ->update(['views' => DB::raw('views + 1')]);

        $post = DB::table('posts')
            ->field('posts.*, users.name as author_name')
            ->join('users', 'posts.user_id = users.id')
            ->where('posts.id', (int)$id)
            ->find();

        if (!$post) {
            return Response::error('文章不存在', 404);
        }

        return Response::success($post);
    }

    /**
     * 创建文章
     * POST /posts
     */
    public function store(Request $request)
    {
        $data = $request->all();

        $validator = new Validator($data, [
            'title' => 'required|string|min:2|max:200',
            'content' => 'required|string|min:10',
            'user_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }

        $data['status'] = 1;
        $data['created_at'] = date('Y-m-d H:i:s');

        $id = DB::table('posts')->insert($data);

        return Response::json(['id' => $id], 201);
    }
}

/**
 * 认证控制器
 */
class AuthController
{
    /**
     * 登录
     * POST /auth/login
     */
    public function login(Request $request)
    {
        $data = $request->all();

        if (empty($data['email']) || empty($data['password'])) {
            return Response::error('邮箱和密码不能为空', 400);
        }

        $user = DB::table('users')
            ->where('email', $data['email'])
            ->find();

        if (!$user) {
            return Response::error('邮箱或密码错误', 401);
        }

        if (!password_verify($data['password'], $user['password'])) {
            return Response::error('邮箱或密码错误', 401);
        }

        // 生成Token
        $token = bin2hex(random_bytes(32));

        // 存储Token
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
    }

    /**
     * 注册
     * POST /auth/register
     */
    public function register(Request $request)
    {
        $data = $request->all();

        $validator = new Validator($data, [
            'name' => 'required|string|min:2|max:50',
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }

        if (DB::table('users')->where('email', $data['email'])->count() > 0) {
            return Response::error('邮箱已被注册', 422);
        }

        $userId = DB::table('users')->insert([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

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
    }

    /**
     * 获取当前用户
     * GET /auth/me
     */
    public function me(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return Response::error('未登录', 401);
        }

        return Response::success($user);
    }
}
