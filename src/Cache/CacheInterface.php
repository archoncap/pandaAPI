<?php
/**
 * 缓存接口
 */

namespace PandaAPI\Cache;

interface CacheInterface
{
    /**
     * 获取缓存
     */
    public function get(string $key, $default = null);

    /**
     * 设置缓存
     */
    public function set(string $key, $value, int $ttl = 0): bool;

    /**
     * 删除缓存
     */
    public function delete(string $key): bool;

    /**
     * 清空所有缓存
     */
    public function clear(): bool;

    /**
     * 检查缓存是否存在
     */
    public function has(string $key): bool;

    /**
     * 批量获取缓存
     */
    public function getMultiple(array $keys, $default = null): array;

    /**
     * 批量设置缓存
     */
    public function setMultiple(array $values, int $ttl = 0): bool;

    /**
     * 批量删除缓存
     */
    public function deleteMultiple(array $keys): bool;

    /**
     * 获取缓存并删除
     */
    public function pull(string $key, $default = null);

    /**
     * 缓存加锁
     */
    public function lock(string $key, int $ttl = 10): bool;

    /**
     * 释放缓存锁
     */
    public function unlock(string $key): bool;

    /**
     * 增加数值
     */
    public function increment(string $key, int $step = 1);

    /**
     * 减少数值
     */
    public function decrement(string $key, int $step = 1);

    /**
     * 获取缓存标签
     */
    public function tag(string $name): CacheInterface;

    /**
     * 清空标签缓存
     */
    public function flush(string $tag): bool;
}
