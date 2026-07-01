<?php
/**
 * CORS中间件
 */

namespace PandaAPI\Middleware;

class CorsMiddleware
{
    /**
     * @var array CORS配置
     */
    protected $config = [
        'origin' => '*',
        'methods' => 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
        'headers' => 'Content-Type, Authorization, X-Requested-With',
        'expose_headers' => 'X-Total-Count, X-Page-Count',
        'max_age' => 86400,
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
        $response = $next($request);

        // 设置CORS头
        $response->header([
            'Access-Control-Allow-Origin' => $this->config['origin'],
            'Access-Control-Allow-Methods' => $this->config['methods'],
            'Access-Control-Allow-Headers' => $this->config['headers'],
            'Access-Control-Expose-Headers' => $this->config['expose_headers'],
            'Access-Control-Max-Age' => $this->config['max_age'],
        ]);

        return $response;
    }
}
