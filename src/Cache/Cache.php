<?php
/**
 * 缓存入口类
 * 管理多种缓存后端
 */

namespace PandaAPI\Cache;

use PandaAPI\Cache\Drivers\FileCache;
use PandaAPI\Cache\Drivers\MemcacheCache;
use PandaAPI\Cache\Drivers\OpenrestyCache;
use PandaAPI\Cache\Drivers\RedisCache;

class Cache
{
    /**
     * @var Cache 单例实例
     */
    protected static $instance;

    /**
     * @var CacheInterface 当前驱动
     */
    protected static $driver;

    /**
     * @var array 配置
     */
    protected static $config = [];

    /**
     * @var QueryCache 查询缓存
     */
    protected static $queryCache;

    /**
     * 禁止实例化
     */
    private function __construct()
    {
    }

    /**
     * 配置缓存
     */
    public static function config(array $config): void
    {
        self::$config = $config;
        $driver = $config['driver'] ?? 'file';
        
        switch ($driver) {
            case 'redis':
                self::$driver = new RedisCache($config['redis'] ?? []);
                break;
            case 'memcache':
                self::$driver = new MemcacheCache($config['memcache'] ?? []);
                break;
            case 'openresty':
                self::$driver = new OpenrestyCache($config['openresty'] ?? []);
                break;
            case 'file':
            default:
                self::$driver = new FileCache($config['file'] ?? []);
                break;
        }
        
        self::$queryCache = new QueryCache(self::$driver);
    }

    /**
     * 获取缓存驱动
     */
    public static function driver(): CacheInterface
    {
        if (self::$driver === null) {
            // 使用默认配置
            self::config([
                'driver' => 'file',
                'file' => ['path' => sys_get_temp_dir() . '/pandaapi_cache']
            ]);
        }
        
        return self::$driver;
    }

    /**
     * 获取查询缓存
     */
    public static function query(): QueryCache
    {
        if (self::$queryCache === null) {
            self::driver(); // 确保驱动已初始化
        }
        return self::$queryCache;
    }

    /**
     * 获取缓存
     */
    public static function get(string $key, $default = null)
    {
        return self::driver()->get($key, $default);
    }

    /**
     * 设置缓存
     */
    public static function set(string $key, $value, int $ttl = 0): bool
    {
        return self::driver()->set($key, $value, $ttl);
    }

    /**
     * 删除缓存
     */
    public static function delete(string $key): bool
    {
        return self::driver()->delete($key);
    }

    /**
     * 清空所有缓存
     */
    public static function clear(): bool
    {
        return self::driver()->clear();
    }

    /**
     * 检查缓存是否存在
     */
    public static function has(string $key): bool
    {
        return self::driver()->has($key);
    }

    /**
     * 批量获取缓存
     */
    public static function getMultiple(array $keys, $default = null): array
    {
        return self::driver()->getMultiple($keys, $default);
    }

    /**
     * 批量设置缓存
     */
    public static function setMultiple(array $values, int $ttl = 0): bool
    {
        return self::driver()->setMultiple($values, $ttl);
    }

    /**
     * 批量删除缓存
     */
    public static function deleteMultiple(array $keys): bool
    {
        return self::driver()->deleteMultiple($keys);
    }

    /**
     * 获取并删除
     */
    public static function pull(string $key, $default = null)
    {
        return self::driver()->pull($key, $default);
    }

    /**
     * 缓存加锁
     */
    public static function lock(string $key, int $ttl = 10): bool
    {
        return self::driver()->lock($key, $ttl);
    }

    /**
     * 释放缓存锁
     */
    public static function unlock(string $key): bool
    {
        return self::driver()->unlock($key);
    }

    /**
     * 增加数值
     */
    public static function increment(string $key, int $step = 1)
    {
        return self::driver()->increment($key, $step);
    }

    /**
     * 减少数值
     */
    public static function decrement(string $key, int $step = 1)
    {
        return self::driver()->decrement($key, $step);
    }

    /**
     * 记住缓存（带回调）
     */
    public static function remember(string $key, callable $callback, int $ttl = 300)
    {
        return self::query()->remember($key, $callback, $ttl);
    }

    /**
     * 标签
     */
    public static function tag(string $name): CacheInterface
    {
        return self::driver()->tag($name);
    }

    /**
     * 清空标签
     */
    public static function flush(string $tag): bool
    {
        return self::driver()->flush($tag);
    }

    /**
     * 快捷方法：remember的别名
     */
    public static function rememberForever(string $key, callable $callback)
    {
        return self::remember($key, $callback, 0);
    }

    /**
     * 清除缓存
     */
    public static function forget(string $key): bool
    {
        return self::driver()->delete($key);
    }

    /**
     * 获取缓存统计
     */
    public static function getStats(): array
    {
        $driver = self::driver();
        
        if ($driver instanceof FileCache) {
            return [
                'size' => $driver->getSize(),
                'count' => $driver->getCount()
            ];
        }
        
        if ($driver instanceof RedisCache) {
            $info = $driver->info();
            return [
                'used_memory' => $info['used_memory_human'] ?? 'N/A',
                'connected_clients' => $info['connected_clients'] ?? 0
            ];
        }
        
        return [];
    }
}
