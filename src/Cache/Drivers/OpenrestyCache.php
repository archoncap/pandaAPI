<?php
/**
 * OpenResty (ngx.shared) 缓存驱动
 * 通过HTTP API访问OpenResty共享内存缓存
 */

namespace PandaAPI\Cache\Drivers;

use PandaAPI\Cache\CacheInterface;

class OpenrestyCache implements CacheInterface
{
    /**
     * @var string OpenResty服务器地址
     */
    protected $host;

    /**
     * @var int 端口
     */
    protected $port;

    /**
     * @var int 连接超时
     */
    protected $timeout;

    /**
     * @var string 前缀
     */
    protected $prefix;

    /**
     * @var string 当前标签
     */
    protected $currentTag = null;

    /**
     * @var resource|null Socket连接
     */
    protected $socket = null;

    /**
     * 构造函数
     */
    public function __construct(array $config = [])
    {
        $this->host = $config['host'] ?? '127.0.0.1';
        $this->port = $config['port'] ?? 8099;
        $this->timeout = $config['timeout'] ?? 1;
        $this->prefix = $config['prefix'] ?? 'quickapi:';
    }

    /**
     * 获取带前缀的键名
     */
    protected function buildKey(string $key): string
    {
        return $this->prefix . $key;
    }

    /**
     * 发送请求到OpenResty
     */
    protected function request(string $method, string $key, $value = null, int $ttl = 0)
    {
        $key = $this->buildKey($key);
        
        // 根据不同的Lua API调整请求格式
        // 这里假设OpenResty有类似 memcached 的协议或者自定义API
        
        switch ($method) {
            case 'get':
                return $this->httpGet($key);
            case 'set':
                return $this->httpSet($key, $value, $ttl);
            case 'delete':
                return $this->httpDelete($key);
            case 'exists':
                return $this->httpExists($key);
            case 'incr':
                return $this->httpIncr($key, $value);
            case 'flush':
                return $this->httpFlush();
            default:
                throw new \InvalidArgumentException("Unknown method: {$method}");
        }
    }

    /**
     * HTTP GET 请求
     */
    protected function httpGet(string $key): mixed
    {
        $url = "http://{$this->host}:{$this->port}/cache/get?key=" . urlencode($key);
        $context = stream_context_create([
            'http' => [
                'timeout' => $this->timeout,
                'method' => 'GET'
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            return null;
        }
        
        $data = json_decode($response, true);
        return $data['value'] ?? null;
    }

    /**
     * HTTP SET 请求
     */
    protected function httpSet(string $key, $value, int $ttl): bool
    {
        $url = "http://{$this->host}:{$this->port}/cache/set";
        
        $postData = json_encode([
            'key' => $key,
            'value' => $value,
            'ttl' => $ttl
        ]);
        
        $context = stream_context_create([
            'http' => [
                'timeout' => $this->timeout,
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => $postData
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            return false;
        }
        
        $result = json_decode($response, true);
        return $result['success'] ?? false;
    }

    /**
     * HTTP DELETE 请求
     */
    protected function httpDelete(string $key): bool
    {
        $url = "http://{$this->host}:{$this->port}/cache/delete?key=" . urlencode($key);
        
        $context = stream_context_create([
            'http' => [
                'timeout' => $this->timeout,
                'method' => 'DELETE'
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            return false;
        }
        
        $result = json_decode($response, true);
        return $result['success'] ?? false;
    }

    /**
     * HTTP EXISTS 请求
     */
    protected function httpExists(string $key): bool
    {
        $url = "http://{$this->host}:{$this->port}/cache/exists?key=" . urlencode($key);
        
        $context = stream_context_create([
            'http' => [
                'timeout' => $this->timeout,
                'method' => 'GET'
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            return false;
        }
        
        $result = json_decode($response, true);
        return $result['exists'] ?? false;
    }

    /**
     * HTTP INCR 请求
     */
    protected function httpIncr(string $key, int $step): int
    {
        $url = "http://{$this->host}:{$this->port}/cache/incr";
        
        $postData = json_encode([
            'key' => $key,
            'step' => $step
        ]);
        
        $context = stream_context_create([
            'http' => [
                'timeout' => $this->timeout,
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => $postData
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            return 0;
        }
        
        $result = json_decode($response, true);
        return $result['value'] ?? 0;
    }

    /**
     * HTTP FLUSH 请求
     */
    protected function httpFlush(): bool
    {
        $url = "http://{$this->host}:{$this->port}/cache/flush";
        
        $context = stream_context_create([
            'http' => [
                'timeout' => $this->timeout,
                'method' => 'DELETE'
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            return false;
        }
        
        $result = json_decode($response, true);
        return $result['success'] ?? false;
    }

    /**
     * 获取缓存
     */
    public function get(string $key, $default = null)
    {
        $value = $this->request('get', $key);
        return $value !== null ? $value : $default;
    }

    /**
     * 设置缓存
     */
    public function set(string $key, $value, int $ttl = 0): bool
    {
        return $this->request('set', $key, $value, $ttl);
    }

    /**
     * 删除缓存
     */
    public function delete(string $key): bool
    {
        return $this->request('delete', $key);
    }

    /**
     * 清空所有缓存
     */
    public function clear(): bool
    {
        return $this->request('flush');
    }

    /**
     * 检查缓存是否存在
     */
    public function has(string $key): bool
    {
        return $this->request('exists', $key);
    }

    /**
     * 批量获取缓存
     */
    public function getMultiple(array $keys, $default = null): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
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
     * 缓存加锁
     */
    public function lock(string $key, int $ttl = 10): bool
    {
        // 尝试设置一个锁键
        return $this->request('set', $key . '_lock', 1, $ttl);
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
        return $this->request('incr', $key, $step);
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
        // 需要服务端支持标签清空
        $tagKey = $this->prefix . 'tag:' . md5($tag);
        return $this->request('delete', $tagKey);
    }
}
