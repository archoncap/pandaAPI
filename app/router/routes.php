<?php
/**
 * 路由配置文件
 * 
 * 所有 API 路由在此定义
 * 格式：HTTP方法 + 路由地址 + 控制器@方法 + 参数约束
 */

use PandaAPI\Route\Route;

// 引入公共函数
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../common/middleware.php';

// 加载 CORS 中间件
middleware_cors();

// ============================================================
// 公开接口（无需认证）
// ============================================================

Route::group(['prefix' => '/api/v1'], function() {

    // ----------------------------------------
    // 健康检查
    // ----------------------------------------
    Route::get('/health', 'HealthController@ping');

    // ----------------------------------------
    // 认证相关
    // ----------------------------------------
    Route::post('/auth/login',    'AuthController@login');      // 用户登录
    Route::post('/auth/register', 'AuthController@register');   // 用户注册
    Route::get('/auth/me',        'AuthController@me');         // 当前用户信息
    Route::post('/auth/logout',   'AuthController@logout');     // 退出登录

    // ----------------------------------------
    // 用户资源（RESTful）
    // ----------------------------------------
    Route::get('/users',           'UserController@index');       // 用户列表
    Route::get('/users/{id}',      'UserController@show');        // 用户详情
    Route::post('/users',          'UserController@store');       // 创建用户
    Route::put('/users/{id}',      'UserController@update');      // 更新用户
    Route::delete('/users/{id}',   'UserController@destroy');     // 删除用户

    // ----------------------------------------
    // 文章资源（RESTful）
    // ----------------------------------------
    Route::get('/posts',           'PostController@index');       // 文章列表
    Route::get('/posts/hot',       'PostController@hot');         // 热门文章
    Route::get('/posts/stats',     'PostController@stats');       // 文章统计
    Route::get('/posts/{id}',      'PostController@show');        // 文章详情
    Route::post('/posts',          'PostController@store');       // 创建文章
    Route::put('/posts/{id}',      'PostController@update');      // 更新文章
    Route::delete('/posts/{id}',   'PostController@destroy');     // 删除文章

    // ----------------------------------------
    // 需要认证的接口
    // ----------------------------------------
    Route::group(['prefix' => '/private'], function() {

        // 个人中心
        Route::get('/profile',    'ProfileController@show');       // 个人资料
        Route::put('/profile',    'ProfileController@update');     // 修改资料

        // 我的文章
        Route::get('/my-posts',   'PostController@mine');          // 我的文章

        // 性能调试（仅开发环境）
        Route::get('/debug',      'DebugController@info');         // 调试信息

    });

});

// 参数约束：{id} 必须为数字
Route::whereNumber('id');

// 404 处理
Route::set404(function($path) {
    response_json(json_error("接口不存在: {$path}", 404), 404);
});
