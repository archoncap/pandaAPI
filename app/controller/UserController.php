<?php
/**
 * 用户控制器
 */

namespace App\Controller;

use QuickAPI\Core\Request;
use QuickAPI\Core\Response;
use QuickAPI\Database\DB;
use QuickAPI\Cache\Cache;
use QuickAPI\Validation\Validator;

class UserController
{
    /**
     * 用户列表
     */
    public function index(Request $request)
    {
        $page = (int)$request->query('page', 1);
        $perPage = (int)$request->query('per_page', 15);
        $status = $request->query('status');

        // 构建查询
        $builder = DB::table('users');

        if ($status !== null) {
            $builder->where('status', $status);
        }

        // 分页
        $result = $builder->paginate($perPage, $page);

        return Response::paginate(
            $result['data'],
            $result['total'],
            $result['current_page'],
            $result['per_page']
        );
    }

    /**
     * 获取单个用户
     */
    public function show(Request $request, $id)
    {
        // 尝试从缓存获取
        $cacheKey = "user:{$id}";

        $user = Cache::remember($cacheKey, function () use ($id) {
            return DB::table('users')
                ->where('id', $id)
                ->find();
        }, 300);

        if (!$user) {
            return Response::error('User not found', 404);
        }

        return Response::success($user);
    }

    /**
     * 创建用户
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
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        // 检查邮箱是否已存在
        $exists = DB::table('users')
            ->where('email', $data['email'])
            ->count();

        if ($exists > 0) {
            return Response::error('Email already exists', 422);
        }

        // 密码加密
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['created_at'] = date('Y-m-d H:i:s');

        // 插入
        $id = DB::table('users')->insert($data);

        // 获取创建的用户
        $user = DB::table('users')->where('id', $id)->find();

        return Response::json($user, 201);
    }

    /**
     * 更新用户
     */
    public function update(Request $request, $id)
    {
        $data = $request->all();

        // 验证
        $validator = new Validator($data, [
            'name' => 'string|min:2|max:50',
            'email' => 'email',
            'password' => 'string|min:6',
        ]);

        if ($validator->fails()) {
            return Response::json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        // 检查用户是否存在
        $user = DB::table('users')->where('id', $id)->find();

        if (!$user) {
            return Response::error('User not found', 404);
        }

        // 如果更新邮箱，检查唯一性
        if (isset($data['email']) && $data['email'] !== $user['email']) {
            $exists = DB::table('users')
                ->where('email', $data['email'])
                ->count();

            if ($exists > 0) {
                return Response::error('Email already exists', 422);
            }
        }

        // 如果更新密码
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $data['updated_at'] = date('Y-m-d H:i:s');

        // 更新
        DB::table('users')->where('id', $id)->update($data);

        // 清除缓存
        Cache::forget("user:{$id}");

        // 获取更新后的用户
        $user = DB::table('users')->where('id', $id)->find();

        return Response::success($user);
    }

    /**
     * 删除用户
     */
    public function destroy(Request $request, $id)
    {
        // 检查用户是否存在
        $user = DB::table('users')->where('id', $id)->find();

        if (!$user) {
            return Response::error('User not found', 404);
        }

        // 删除
        DB::table('users')->where('id', $id)->delete();

        // 清除缓存
        Cache::forget("user:{$id}");

        return Response::success(['message' => 'User deleted successfully']);
    }
}
