<?php
/**
 * 认证控制器
 */

namespace App\Controller;

use QuickAPI\Core\Request;
use QuickAPI\Core\Response;
use QuickAPI\Database\DB;
use QuickAPI\Cache\Cache;

class AuthController
{
    /**
     * 用户登录
     */
    public function login(Request $request)
    {
        $data = $request->all();

        // 验证必要字段
        if (empty($data['email']) || empty($data['password'])) {
            return Response::error('Email and password are required', 400);
        }

        // 查找用户
        $user = DB::table('users')
            ->where('email', $data['email'])
            ->find();

        if (!$user) {
            return Response::error('Invalid credentials', 401);
        }

        // 验证密码
        if (!password_verify($data['password'], $user['password'])) {
            return Response::error('Invalid credentials', 401);
        }

        // 检查用户状态
        if (isset($user['status']) && $user['status'] != 1) {
            return Response::error('Account is disabled', 403);
        }

        // 生成Token
        $token = $this->generateToken($user);

        // 存储Token
        Cache::set("token:{$token}", [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'created_at' => time(),
        ], 86400 * 7); // 7天过期

        return Response::success([
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
            ]
        ]);
    }

    /**
     * 用户注册
     */
    public function register(Request $request)
    {
        $data = $request->all();

        // 验证
        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            return Response::error('Name, email and password are required', 400);
        }

        // 检查邮箱唯一性
        $exists = DB::table('users')
            ->where('email', $data['email'])
            ->count();

        if ($exists > 0) {
            return Response::error('Email already registered', 422);
        }

        // 创建用户
        $userId = DB::table('users')->insert([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // 生成Token
        $user = DB::table('users')->where('id', $userId)->find();
        $token = $this->generateToken($user);

        // 存储Token
        Cache::set("token:{$token}", [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'created_at' => time(),
        ], 86400 * 7);

        return Response::json([
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
            ]
        ], 201);
    }

    /**
     * 刷新Token
     */
    public function refresh(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return Response::error('Unauthorized', 401);
        }

        // 生成新Token
        $newToken = $this->generateToken($user);

        // 存储新Token
        Cache::set("token:{$newToken}", [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'created_at' => time(),
        ], 86400 * 7);

        return Response::success([
            'token' => $newToken,
        ]);
    }

    /**
     * 生成Token
     */
    protected function generateToken(array $user): string
    {
        $data = $user['id'] . $user['email'] . time() . random_bytes(16);
        return hash('sha256', $data);
    }
}
