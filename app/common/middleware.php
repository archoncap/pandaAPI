<?php
/**
 * 公共中间件定义
 * 
 * 全局可用的中间件函数
 */

use PandaAPI\Core\Response;

if (!function_exists('middleware_cors')) {
    /**
     * CORS 跨域中间件
     */
    function middleware_cors(): void
    {
        $origin = config('cors.origin', '*');
        $methods = config('cors.methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $headers = config('cors.headers', 'Content-Type, Authorization');

        header("Access-Control-Allow-Origin: {$origin}");
        header("Access-Control-Allow-Methods: {$methods}");
        header("Access-Control-Allow-Headers: {$headers}");
        header('Access-Control-Max-Age: 86400');

        // 预检请求直接返回
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}

if (!function_exists('middleware_auth')) {
    /**
     * Token 认证中间件
     * 
     * @return array|null 认证通过返回用户信息，失败返回 null
     */
    function middleware_auth(): ?array
    {
        $user = current_user();
        if (!$user) {
            response_json(json_error('未登录或Token已过期', 401), 401);
        }
        return $user;
    }
}

if (!function_exists('middleware_throttle')) {
    /**
     * 频率限制中间件
     * 
     * @param int $maxAttempts 最大请求次数
     * @param int $decayMinutes 时间窗口（分钟）
     */
    function middleware_throttle(int $maxAttempts = 60, int $decayMinutes = 1): void
    {
        $ip = get_client_ip();
        $key = "throttle:{$ip}";
        $cache = \PandaAPI\Cache\Cache::class;

        $count = $cache::get($key, 0);

        if ($count >= $maxAttempts) {
            response_json(json_error('请求过于频繁，请稍后再试', 429), 429);
        }

        $cache::increment($key);
        $cache::expire($key, $decayMinutes * 60);
    }
}
