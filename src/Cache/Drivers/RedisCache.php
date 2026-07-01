<?php
/**
 * Redis缓存驱动
 */

namespace PandaAPI\Cache\Drivers;

use PandaAPI\Cache\CacheInterface;
use Redis;
use RedisException;

class RedisCache implements CacheInterface
{
    /**
     * @var Redis Redis连接
     */
    protected $redis;

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
     * 连接Redis
     */
    protected function connect(): void
    {
        $this->redis = new Redis();
        
        $host = $this->config['host'] ?? '127.0.0.1';
        $port = $this->config['port'] ?? 6379;
        $timeout = $this->config['timeout'] ?? 0;
        $password = $this->config['password'] ?? null;
        $database = $this->config['database'] ?? 0;
        
        try {
            if ($timeout > 0) {
                $this->redis->connect($host, $port, $timeout);
            } else {
                $this->redis->connect($host, $port);
            }
            
            if ($password !== null) {
                $this->redis->auth($password);
            }
            
            $this->redis->select($database);
            
            // 设置序列化
            $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        } catch (RedisException $e) {
            throw new \RuntimeException("Redis connection failed: " . $e->getMessage());
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
        $value = $this->redis->get($this->buildKey($key));
        return $value !== false ? $value : $default;
    }

    /**
     * 设置缓存
     */
    public function set(string $key, $value, int $ttl = 0): bool
    {
        if ($ttl > 0) {
            return $this->redis->setex($this->buildKey($key), $ttl, $value);
        }
        return $this->redis->set($this->buildKey($key), $value);
    }

    /**
     * 删除缓存
     */
    public function delete(string $key): bool
    {
        return $this->redis->del($this->buildKey($key)) > 0;
    }

    /**
     * 清空所有缓存
     */
    public function clear(): bool
    {
        if ($this->prefix) {
            // 只清当前前缀的缓存
            $keys = $this->redis->keys($this->prefix . '*');
            if (!empty($keys)) {
                $this->redis->del($keys);
            }
        } else {
            $this->redis->flushDB();
        }
        return true;
    }

    /**
     * 检查缓存是否存在
     */
    public function has(string $key): bool
    {
        return $this->redis->exists($this->buildKey($key)) > 0;
    }

    /**
     * 批量获取缓存
     */
    public function getMultiple(array $keys, $default = null): array
    {
        $result = [];
        $fullKeys = array_map([$this, 'buildKey'], $keys);
        
        $values = $this->redis->mget($fullKeys);
        
        foreach ($keys as $index => $key) {
            $result[$key] = $values[$index] !== false ? $values[$index] : $default;
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
        $fullKeys = array_map([$this, 'buildKey'], $keys);
        $this->redis->del($fullKeys);
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
     * 缓存加锁
     */
    public function lock(string $key, int $ttl = 10): bool
    {
        return $this->redis->set(
            $this->buildKey($key . '_lock'),
            1,
            ['NX', 'EX' => $ttl]
        ) !== false;
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
        $newValue = $this->redis->incrby($this->buildKey($key), $step);
        return (int)$newValue;
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
        $tagKey = $this->prefix . 'tag:' . md5($tag);
        $keys = $this->redis->smembers($tagKey);
        
        if (!empty($keys)) {
            $this->redis->del($keys);
        }
        $this->redis->del($tagKey);
        
        return true;
    }

    /**
     * 设置过期时间
     */
    public function expire(string $key, int $ttl): bool
    {
        return $this->redis->expire($this->buildKey($key), $ttl);
    }

    /**
     * 获取剩余生存时间
     */
    public function ttl(string $key): int
    {
        $ttl = $this->redis->ttl($this->buildKey($key));
        return $ttl >= 0 ? $ttl : -1;
    }

    /**
     * 获取Redis实例
     */
    public function getRedis(): Redis
    {
        return $this->redis;
    }

    /**
     * 执行Redis命令
     */
    public function __call($method, $args)
    {
        return call_user_func_array([$this->redis, $method], $args);
    }
}
