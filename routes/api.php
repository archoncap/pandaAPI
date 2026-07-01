<?php
/**
 * API路由定义
 */

use QuickAPI\Route\Route;
use QuickAPI\Core\Response;
use QuickAPI\Database\DB;
use QuickAPI\Cache\Cache;

/**
 * API版本组 v1
 */
Route::group(['prefix' => '/api/v1', 'middleware' => ['Cors']], function () {
    
    // 公共接口 - 无需认证
    Route::group(['middleware' => []], function () {
        
        // 健康检查
        Route::get('/health', function () {
            return Response::success([
                'status' => 'ok',
                'time' => date('Y-m-d H:i:s'),
                'version' => '1.0.0'
            ]);
        });

        // 用户相关
        Route::get('/users', 'App\\Controller\\UserController@index');
        Route::get('/users/{id}', 'App\\Controller\\UserController@show')->where('id', '[0-9]+');
        Route::post('/users', 'App\\Controller\\UserController@store');
        Route::put('/users/{id}', 'App\\Controller\\UserController@update')->where('id', '[0-9]+');
        Route::delete('/users/{id}', 'App\\Controller\\UserController@destroy')->where('id', '[0-9]+');

        // 认证相关
        Route::post('/auth/login', 'App\\Controller\\AuthController@login');
        Route::post('/auth/register', 'App\\Controller\\AuthController@register');
        Route::post('/auth/refresh', 'App\\Controller\\AuthController@refresh');
    });

    // 需要认证的接口
    Route::group(['middleware' => ['Auth']], function () {
        
        // 个人资料
        Route::get('/profile', 'App\\Controller\\ProfileController@show');
        Route::put('/profile', 'App\\Controller\\ProfileController@update');
        
        // 文章相关
        Route::get('/articles', 'App\\Controller\\ArticleController@index');
        Route::get('/articles/{id}', 'App\\Controller\\ArticleController@show');
        Route::post('/articles', 'App\\Controller\\ArticleController@store');
        Route::put('/articles/{id}', 'App\\Controller\\ArticleController@update');
        Route::delete('/articles/{id}', 'App\\Controller\\ArticleController@destroy');
    });
});

/**
 * API版本组 v2
 */
Route::group(['prefix' => '/api/v2', 'middleware' => ['Cors']], function () {
    
    // 健康检查
    Route::get('/health', function () {
        return Response::success([
            'status' => 'ok',
            'time' => date('Y-m-d H:i:s'),
            'version' => '2.0.0'
        ]);
    });
});

/**
 * 全局404处理
 */
Route::set404(function ($path) {
    return Response::error('Endpoint not found: ' . $path, 404);
});
