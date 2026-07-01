<?php
/**
 * 文件缓存驱动
 */

namespace PandaAPI\Cache\Drivers;

use PandaAPI\Cache\CacheInterface;
use PandaAPI\Exception\CacheException;

class FileCache implements CacheInterface
{
    /**
     * @var string 缓存目录
     */
    protected $path;

    /**
     * @var string 文件扩展名
     */
    protected $ext = '.cache';

    /**
     * @var array 标签索引
     */
    protected $tags = [];

    /**
     * @var string 当前标签
     */
    protected $currentTag = null;

    /**
     * 构造函数
     */
    public function __construct(array $config = [])
    {
        $this->path = $config['path'] ?? sys_get_temp_dir() . '/quickapi_cache';
        
        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    /**
     * 获取缓存文件路径
     */
    protected function getFilePath(string $key): string
    {
        $hash = md5($key);
        $dir = $this->path . '/' . substr($hash, 0, 2);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        return $dir . '/' . $hash . $this->ext;
    }

    /**
     * 获取缓存
     */
    public function get(string $key, $default = null)
    {
        $file = $this->getFilePath($key);
        
        if (!file_exists($file)) {
            return $default;
        }

        $content = file_get_contents($file);
        $data = unserialize($content);

        // 检查过期
        if ($data['expire'] > 0 && $data['expire'] < time()) {
            $this->delete($key);
            return $default;
        }

        return $data['value'];
    }

    /**
     * 设置缓存
     */
    public function set(string $key, $value, int $ttl = 0): bool
    {
        $file = $this->getFilePath($key);
        
        $expire = $ttl > 0 ? time() + $ttl : 0;
        
        $data = [
            'value' => $value,
            'expire' => $expire,
            'tag' => $this->currentTag,
            'time' => time()
        ];

        $result = file_put_contents($file, serialize($data), LOCK_EX) !== false;

        if ($result && $this->currentTag !== null) {
            $this->addToTag($key);
        }

        return $result;
    }

    /**
     * 删除缓存
     */
    public function delete(string $key): bool
    {
        $file = $this->getFilePath($key);
        
        if (file_exists($file)) {
            return unlink($file);
        }

        return true;
    }

    /**
     * 清空所有缓存
     */
    public function clear(): bool
    {
        if (!is_dir($this->path)) {
            return true;
        }

        $files = glob($this->path . '/*' . $this->ext);
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        // 清空标签索引
        $tagFiles = glob($this->path . '/tags_*');
        foreach ($tagFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        $this->tags = [];
        
        return true;
    }

    /**
     * 检查缓存是否存在
     */
    public function has(string $key): bool
    {
        $file = $this->getFilePath($key);
        
        if (!file_exists($file)) {
            return false;
        }

        $content = file_get_contents($file);
        $data = unserialize($content);

        // 检查过期
        if ($data['expire'] > 0 && $data['expire'] < time()) {
            $this->delete($key);
            return false;
        }

        return true;
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
        $lockKey = $key . '_lock';
        $lockFile = $this->getFilePath($lockKey);
        
        if (file_exists($lockFile)) {
            $content = file_get_contents($lockFile);
            $data = unserialize($content);
            
            if ($data['expire'] > time()) {
                return false; // 锁已存在且未过期
            }
        }
        
        $data = [
            'value' => 1,
            'expire' => time() + $ttl,
            'tag' => null,
            'time' => time()
        ];
        
        return file_put_contents($lockFile, serialize($data), LOCK_EX) !== false;
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
        $value = $this->get($key, 0);
        $value += $step;
        $this->set($key, $value);
        return $value;
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
     * 添加到标签索引
     */
    protected function addToTag(string $key): void
    {
        if ($this->currentTag === null) {
            return;
        }

        $tagFile = $this->path . '/tags_' . md5($this->currentTag);
        
        $keys = [];
        if (file_exists($tagFile)) {
            $content = file_get_contents($tagFile);
            $keys = unserialize($content) ?: [];
        }

        if (!in_array($key, $keys)) {
            $keys[] = $key;
            file_put_contents($tagFile, serialize($keys), LOCK_EX);
        }
    }

    /**
     * 清空标签缓存
     */
    public function flush(string $tag): bool
    {
        $tagFile = $this->path . '/tags_' . md5($tag);
        
        if (!file_exists($tagFile)) {
            return true;
        }

        $content = file_get_contents($tagFile);
        $keys = unserialize($content) ?: [];

        foreach ($keys as $key) {
            $this->delete($key);
        }

        unlink($tagFile);
        
        return true;
    }

    /**
     * 获取缓存目录大小
     */
    public function getSize(): int
    {
        $size = 0;
        $files = glob($this->path . '/*' . $this->ext);
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $size += filesize($file);
            }
        }
        
        return $size;
    }

    /**
     * 获取缓存数量
     */
    public function getCount(): int
    {
        $files = glob($this->path . '/*' . $this->ext);
        return count(array_filter($files, 'is_file'));
    }

    /**
     * 清理过期缓存
     */
    public function prune(): int
    {
        $count = 0;
        $files = glob($this->path . '/*' . $this->ext);
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $content = file_get_contents($file);
                $data = unserialize($content);
                
                if ($data['expire'] > 0 && $data['expire'] < time()) {
                    unlink($file);
                    $count++;
                }
            }
        }
        
        return $count;
    }
}
