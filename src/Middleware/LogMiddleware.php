<?php
/**
 * 日志中间件
 */

namespace PandaAPI\Middleware;

class LogMiddleware
{
    /**
     * @var array 日志配置
     */
    protected $config = [
        'path' => null,
        'level' => 'info',
    ];

    /**
     * @var float 请求开始时间
     */
    protected $startTime;

    /**
     * 构造函数
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
        $this->startTime = microtime(true);
    }

    /**
     * 处理请求
     */
    public function handle($request, callable $next)
    {
        $method = $request->method();
        $uri = $request->uri();
        
        // 执行请求
        $response = $next($request);
        
        // 记录日志
        $duration = (microtime(true) - $this->startTime) * 1000;
        
        $log = sprintf(
            "[%s] %s %s - Status: %d - Time: %.2fms",
            date('Y-m-d H:i:s'),
            $method,
            $uri,
            $response->getStatusCode(),
            $duration
        );
        
        $this->writeLog($log);
        
        return $response;
    }

    /**
     * 写入日志
     */
    protected function writeLog(string $message): void
    {
        $path = $this->config['path'] ?? sys_get_temp_dir() . '/pandaapi.log';
        $dir = dirname($path);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($path, $message . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
