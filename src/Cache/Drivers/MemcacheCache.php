<?php
/**
 * Memcache缓存驱动
 */

namespace PandaAPI\Cache\Drivers;

use PandaAPI\Cache\CacheInterface;
use Memcache;

class MemcacheCache implements CacheInterface
{
    /**
     * @var Memcache Memcache连接
     */
    protected $memcache;

    /**
     * @var array 配置
     */
    protected $config;

    /**
     * @var string 前缀
     */
    protected $prefix;

    /**
     * @var string 当前标签
     */
    protected $currentTag = null;

    /**
     * 构造函数
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->prefix = $config['prefix'] ?? 'quickapi:';
        $this->connect();
    }

    /**
     * 连接Memcache
     */
    protected function connect(): void
    {
        $this->memcache = new Memcache();
        
        $host = $this->config['host'] ?? '127.0.0.1';
        $port = $this->config['port'] ?? 11211;
        $persistent = $this->config['persistent'] ?? false;
        
        if (!$this->memcache->addServer($host, $port, $persistent)) {
            throw new \RuntimeException("Memcache connection failed to {$host}:{$port}");
        }
    }

    /**
     * 获取带前缀的键名
     */
    protected function buildKey(string $key): string
    {
        return $this->prefix . $key;
    }

    /**
     * 获取缓存
     */
    public function get(string $key, $default = null)
    {
        $value = $this->memcache->get($this->buildKey($key));
        return $value !== false ? $value : $default;
    }

    /**
     * 设置缓存
     */
    public function set(string $key, $value, int $ttl = 0): bool
    {
        if ($ttl > 0) {
            return $this->memcache->set($this->buildKey($key), $value, 0, $ttl);
        }
        // Memcache默认过期时间0表示永不过期
        return $this->memcache->set($this->buildKey($key), $value, 0, 0);
    }

    /**
     * 删除缓存
     */
    public function delete(string $key): bool
    {
        return $this->memcache->delete($this->buildKey($key), 0);
    }

    /**
     * 清空所有缓存
     */
    public function clear(): bool
    {
        return $this->memcache->flush();
    }

    /**
     * 检查缓存是否存在
     */
    public function has(string $key): bool
    {
        $this->memcache->get($this->buildKey($key));
        return $this->memcache->getResultCode() === MEMCACHED_RES_SUCCESS;
    }

    /**
     * 批量获取缓存
     */
    public function getMultiple(array $keys, $default = null): array
    {
        $fullKeys = array_map([$this, 'buildKey'], $keys);
        $values = $this->memcache->get($fullKeys);
        
        $result = [];
        foreach ($keys as $index => $key) {
            $result[$key] = $values[$fullKeys[$index]] ?? $default;
        }
        
        return $result;
    }

    /**
     * 批量设置缓存
     */
    public function setMultiple(array $values, int $ttl = 0): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    /**
     * 批量删除缓存
     */
    public function deleteMultiple(array $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    /**
     * 获取并删除
     */
    public function pull(string $key, $default = null)
    {
        $value = $this->get($key, $default);
        $this->delete($key);
        return $value;
    }

    /**
     * 缓存加锁 (使用add的原子性)
     */
    public function lock(string $key, int $ttl = 10): bool
    {
        return $this->memcache->add(
            $this->buildKey($key . '_lock'),
            1,
            0,
            $ttl
        );
    }

    /**
     * 释放缓存锁
     */
    public function unlock(string $key): bool
    {
        return $this->delete($key . '_lock');
    }

    /**
     * 增加数值
     */
    public function increment(string $key, int $step = 1)
    {
        // 先确保键存在
        if (!$this->has($key)) {
            $this->set($key, 0);
        }
        
        return $this->memcache->increment($this->buildKey($key), $step);
    }

    /**
     * 减少数值
     */
    public function decrement(string $key, int $step = 1)
    {
        return $this->increment($key, -$step);
    }

    /**
     * 获取标签
     */
    public function tag(string $name): CacheInterface
    {
        $clone = clone $this;
        $clone->currentTag = $name;
        return $clone;
    }

    /**
     * 清空标签缓存
     */
    public function flush(string $tag): bool
    {
        // Memcache不支持标签，需要维护一个标签索引
        $tagKey = $this->prefix . 'tag:' . md5($tag);
        $keys = $this->memcache->get($tagKey);
        
        if (!empty($keys)) {
            foreach ($keys as $key) {
                $this->delete($key);
            }
        }
        
        $this->delete($tagKey);
        return true;
    }

    /**
     * 获取Memcache实例
     */
    public function getMemcache(): Memcache
    {
        return $this->memcache;
    }

    /**
     * 获取服务器统计信息
     */
    public function getStats(): array
    {
        return $this->memcache->getStats();
    }

    /**
     * 获取服务器版本
     */
    public function getVersion(): string
    {
        $stats = $this->memcache->getVersion();
        return is_array($stats) ? implode(',', $stats) : '';
    }
}
