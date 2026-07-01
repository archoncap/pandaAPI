<?php
/**
 * 数据库测试
 */

namespace QuickAPI\Test;

use PHPUnit\Framework\TestCase;
use QuickAPI\Database\Connection;
use QuickAPI\Database\DB;
use QuickAPI\Database\Builder;

class DBTest extends TestCase
{
    protected function setUp(): void
    {
        // 配置测试数据库
        DB::config([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
    }

    public function testConfig(): void
    {
        $this->assertTrue(true);
    }

    public function testInsert(): void
    {
        // 创建测试表
        DB::execute("CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT)");
        
        // 测试插入
        $id = DB::table('users')->insert([
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);
        
        $this->assertGreaterThan(0, $id);
    }

    public function testSelect(): void
    {
        // 创建测试表
        DB::execute("CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT)");
        
        // 插入数据
        DB::table('users')->insert(['name' => 'User 1', 'email' => 'user1@example.com']);
        DB::table('users')->insert(['name' => 'User 2', 'email' => 'user2@example.com']);
        
        // 查询
        $users = DB::table('users')->select();
        
        $this->assertCount(2, $users);
    }

    public function testWhere(): void
    {
        // 创建测试表
        DB::execute("CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT, status INTEGER)");
        
        // 插入数据
        DB::table('users')->insert(['name' => 'Active User', 'email' => 'active@example.com', 'status' => 1]);
        DB::table('users')->insert(['name' => 'Inactive User', 'email' => 'inactive@example.com', 'status' => 0]);
        
        // 条件查询
        $activeUsers = DB::table('users')->where('status', 1)->select();
        
        $this->assertCount(1, $activeUsers);
        $this->assertEquals('Active User', $activeUsers[0]['name']);
    }

    public function testFind(): void
    {
        // 创建测试表
        DB::execute("CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT)");
        
        // 插入数据
        $id = DB::table('users')->insert(['name' => 'Test User', 'email' => 'test@example.com']);
        
        // 查询单条
        $user = DB::table('users')->where('id', $id)->find();
        
        $this->assertNotNull($user);
        $this->assertEquals('Test User', $user['name']);
    }

    public function testUpdate(): void
    {
        // 创建测试表
        DB::execute("CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT)");
        
        // 插入数据
        $id = DB::table('users')->insert(['name' => 'Old Name', 'email' => 'test@example.com']);
        
        // 更新
        DB::table('users')->where('id', $id)->update(['name' => 'New Name']);
        
        // 验证
        $user = DB::table('users')->where('id', $id)->find();
        
        $this->assertEquals('New Name', $user['name']);
    }

    public function testDelete(): void
    {
        // 创建测试表
        DB::execute("CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT)");
        
        // 插入数据
        $id = DB::table('users')->insert(['name' => 'To Delete', 'email' => 'delete@example.com']);
        
        // 删除
        DB::table('users')->where('id', $id)->delete();
        
        // 验证
        $user = DB::table('users')->where('id', $id)->find();
        
        $this->assertNull($user);
    }

    public function testCount(): void
    {
        // 创建测试表
        DB::execute("CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT)");
        
        // 插入多条数据
        DB::table('users')->insert(['name' => 'User 1', 'email' => 'user1@example.com']);
        DB::table('users')->insert(['name' => 'User 2', 'email' => 'user2@example.com']);
        DB::table('users')->insert(['name' => 'User 3', 'email' => 'user3@example.com']);
        
        // 统计
        $count = DB::table('users')->count();
        
        $this->assertEquals(3, $count);
    }

    public function testPaginate(): void
    {
        // 创建测试表
        DB::execute("CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT)");
        
        // 插入多条数据
        for ($i = 1; $i <= 20; $i++) {
            DB::table('users')->insert(['name' => "User {$i}", 'email' => "user{$i}@example.com"]);
        }
        
        // 分页查询
        $result = DB::table('users')->paginate(5, 2);
        
        $this->assertEquals(20, $result['total']);
        $this->assertEquals(5, $result['per_page']);
        $this->assertEquals(2, $result['current_page']);
        $this->assertCount(5, $result['data']);
    }

    public function testChaining(): void
    {
        // 创建测试表
        DB::execute("CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT, status INTEGER, created_at TEXT)");
        
        // 插入测试数据
        DB::table('users')->insert(['name' => 'Active User', 'email' => 'active@example.com', 'status' => 1, 'created_at' => '2024-01-01']);
        DB::table('users')->insert(['name' => 'Another Active', 'email' => 'another@example.com', 'status' => 1, 'created_at' => '2024-01-02']);
        DB::table('users')->insert(['name' => 'Inactive', 'email' => 'inactive@example.com', 'status' => 0, 'created_at' => '2024-01-03']);
        
        // 链式调用
        $users = DB::table('users')
            ->field('id, name, email')
            ->where('status', 1)
            ->order('created_at', 'desc')
            ->limit(10)
            ->select();
        
        $this->assertCount(2, $users);
        $this->assertEquals('Another Active', $users[0]['name']);
    }

    public function testQueryStats(): void
    {
        // 创建测试表
        DB::execute("CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)");
        
        // 执行查询
        DB::table('users')->select();
        DB::table('users')->insert(['name' => 'Test']);
        DB::table('users')->count();
        
        // 获取统计
        $stats = DB::getQueryStats();
        
        $this->assertGreaterThan(0, $stats['count']);
    }

    public function testTransaction(): void
    {
        // 创建测试表
        DB::execute("CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, balance REAL)");
        
        // 事务测试
        DB::transaction(function () {
            DB::table('users')->insert(['name' => 'User 1', 'balance' => 100]);
            DB::table('users')->insert(['name' => 'User 2', 'balance' => 100]);
        });
        
        // 验证数据已插入
        $count = DB::table('users')->count();
        
        $this->assertEquals(2, $count);
    }
}
