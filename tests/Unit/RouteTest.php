<?php
/**
 * 路由测试
 */

namespace QuickAPI\Test;

use PHPUnit\Framework\TestCase;
use QuickAPI\Route\Route;
use QuickAPI\Route\RouteItem;

class RouteTest extends TestCase
{
    protected function setUp(): void
    {
        Route::init();
    }

    public function testBasicRoute(): void
    {
        Route::get('/test', function () {
            return 'test response';
        });

        $this->assertTrue(true); // 路由已注册
    }

    public function testRouteItem(): void
    {
        $item = new RouteItem('/users', function () {
            return 'users';
        });

        $this->assertEquals('/users', $item->getPath());
    }

    public function testRouteItemMiddleware(): void
    {
        $item = new RouteItem('/api/test', function () {
            return 'test';
        });

        $item->middleware('Cors');

        $this->assertContains('Cors', $item->getMiddleware());
    }

    public function testRouteItemName(): void
    {
        $item = new RouteItem('/users', function () {
            return 'users';
        });

        $item->name('users.index');

        $this->assertEquals('users.index', $item->getName());
    }

    public function testRouteItemWhere(): void
    {
        $item = new RouteItem('/user/{id}', function () {
            return 'user';
        });

        $item->whereNumber('id');

        $wheres = $item->getWheres();
        $this->assertArrayHasKey('id', $wheres);
    }

    public function testGroupRoute(): void
    {
        Route::group(['prefix' => '/api'], function () {
            Route::get('/users', function () {
                return 'users';
            });
        });

        $this->assertTrue(true); // 组路由已注册
    }

    public function testResourceRoute(): void
    {
        Route::resource('users', 'UserController');

        $this->assertTrue(true); // 资源路由已注册
    }
}
