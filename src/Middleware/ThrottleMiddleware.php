<?php
/**
 * 限流中间件
 */

namespace PandaAPI\Middleware;

use PandaAPI\Cache\Cache;

class ThrottleMiddleware
{
    /**
     * @var array 配置
     */
    protected $config = [
        'max_attempts' => 60,
        'decay_minutes' => 1,
        'prefix' => 'throttle:',
    ];

    /**
     * 构造函数
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 处理请求
     */
    public function handle($request, callable $next)
    {
        $key = $this->resolveRequestKey($request);
        
        $maxAttempts = $this->config['max_attempts'];
        $decayMinutes = $this->config['decay_minutes'];
        
        // 获取当前尝试次数
        $attempts = (int)Cache::get($key, 0);
        
        if ($attempts >= $maxAttempts) {
            $retryAfter = Cache::ttl($key);
            
            return \PandaAPI\Core\Response::json([
                'error' => 'Too Many Requests',
                'message' => 'Rate limit exceeded. Please try again later.',
                'retry_after' => $retryAfter
            ], 429, [
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
                'Retry-After' => $retryAfter ?: $decayMinutes * 60
            ]);
        }
        
        // 增加计数
        if ($attempts === 0) {
            Cache::set($key, 1, $decayMinutes * 60);
        } else {
            Cache::increment($key);
        }
        
        // 执行请求
        $response = $next($request);
        
        // 添加限流头
        $remaining = max(0, $maxAttempts - $attempts - 1);
        
        $response->header([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remaining,
        ]);
        
        return $response;
    }

    /**
     * 解析请求键
     */
    protected function resolveRequestKey($request): string
    {
        $prefix = $this->config['prefix'];
        
        // 基于IP
        $ip = $request->ip();
        
        // 基于路由
        $route = $request->route();
        
        return $prefix . md5($ip . ':' . ($route['path'] ?? ''));
    }

    /**
     * 重置限流计数
     */
    public function reset($request): void
    {
        $key = $this->resolveRequestKey($request);
        Cache::forget($key);
    }
}
