<?php
/**
 * 查询缓存
 * 自动缓存数据库查询结果
 */

namespace PandaAPI\Cache;

use Closure;

class QueryCache
{
    /**
     * @var CacheInterface 缓存实例
     */
    protected $cache;

    /**
     * @var int 默认TTL
     */
    protected $defaultTtl = 300;

    /**
     * @var bool 是否启用
     */
    protected $enabled = true;

    /**
     * 构造函数
     */
    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * 缓存查询结果
     */
    public function remember(string $key, Closure $callback, int $ttl = null): mixed
    {
        if (!$this->enabled) {
            return $callback();
        }

        $ttl = $ttl ?? $this->defaultTtl;

        $value = $this->cache->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->cache->set($key, $value, $ttl);

        return $value;
    }

    /**
     * 缓存查询结果，如果存在则返回
     */
    public function get(string $key, Closure $callback = null, int $ttl = null): mixed
    {
        if (!$this->enabled) {
            return $callback ? $callback() : null;
        }

        $ttl = $ttl ?? $this->defaultTtl;
        $value = $this->cache->get($key);

        if ($value !== null) {
            return $value;
        }

        if ($callback) {
            $value = $callback();
            $this->cache->set($key, $value, $ttl);
            return $value;
        }

        return null;
    }

    /**
     * 清除查询缓存
     */
    public function forget(string $key): bool
    {
        return $this->cache->delete($key);
    }

    /**
     * 清除标签下的所有缓存
     */
    public function flush(string $tag): bool
    {
        return $this->cache->flush($tag);
    }

    /**
     * 启用缓存
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * 禁用缓存
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * 设置默认TTL
     */
    public function setDefaultTtl(int $ttl): void
    {
        $this->defaultTtl = $ttl;
    }

    /**
     * 获取默认TTL
     */
    public function getDefaultTtl(): int
    {
        return $this->defaultTtl;
    }

    /**
     * 检查是否启用
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * 生成查询缓存键
     */
    public function makeKey(string $table, array $conditions = [], string $suffix = ''): string
    {
        $hash = md5($table . json_encode($conditions) . $suffix);
        return 'query:' . $hash;
    }
}
