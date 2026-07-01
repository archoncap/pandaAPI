<?php
/**
 * 应用核心类
 * 管理整个应用的启动和运行
 */

namespace PandaAPI\Core;

use PandaAPI\Route\Route;
use PandaAPI\Route\Middleware;
use PandaAPI\Exception\ApiException;

class App
{
    /**
     * @var App 单例实例
     */
    protected static $instance;

    /**
     * @var array 配置
     */
    protected $config = [];

    /**
     * @var Request 当前请求
     */
    protected $request;

    /**
     * @var Response 当前响应
     */
    protected $response;

    /**
     * @var array 全局中间件
     */
    protected $middleware = [];

    /**
     * @var bool 是否已启动
     */
    protected $booted = false;

    /**
     * @var float 启动时间
     */
    protected $startTime;

    /**
     * @var array 性能统计
     */
    protected $stats = [
        'queries' => 0,
        'time' => 0,
        'memory' => 0,
    ];

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->startTime = microtime(true);
        // 关闭Session以提升性能
        $this->disableSession();
    }

    /**
     * 获取单例实例
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 关闭PHP Session
     */
    protected function disableSession(): void
    {
        // 如果session已启动，先关闭
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        
        // 禁用session自动启动
        ini_set('session.auto_start', '0');
        
        // 可选：禁用session相关功能以进一步提升性能
        // ini_set('session.use_strict_mode', '1');
        // ini_set('session.use_only_cookies', '1');
    }

    /**
     * 引导应用
     */
    public function bootstrap(): void
    {
        if ($this->booted) {
            return;
        }

        $this->startTime = microtime(true);
        
        // 初始化中间件
        Middleware::boot();
        
        $this->booted = true;
    }

    /**
     * 配置应用
     */
    public function config(array $config): self
    {
        $this->config = array_merge($this->config, $config);
        
        // 配置数据库
        if (isset($config['database'])) {
            \PandaAPI\Database\DB::config($config['database']);
        }
        
        // 配置缓存
        if (isset($config['cache'])) {
            \PandaAPI\Cache\Cache::config($config['cache']);
        }
        
        return $this;
    }

    /**
     * 设置全局中间件
     */
    public function middleware(array $middleware): self
    {
        $this->middleware = $middleware;
        Middleware::global($middleware);
        return $this;
    }

    /**
     * 添加中间件
     */
    public function addMiddleware(string $middleware): self
    {
        $this->middleware[] = $middleware;
        Middleware::addGlobal($middleware);
        return $this;
    }

    /**
     * 设置请求
     */
    public function setRequest(Request $request): self
    {
        $this->request = $request;
        return $this;
    }

    /**
     * 设置响应
     */
    public function setResponse(Response $response): self
    {
        $this->response = $response;
        return $this;
    }

    /**
     * 获取请求
     */
    public function request(): Request
    {
        if ($this->request === null) {
            $this->request = new Request();
        }
        return $this->request;
    }

    /**
     * 获取响应
     */
    public function response(): Response
    {
        if ($this->response === null) {
            $this->response = new Response();
        }
        return $this->response;
    }

    /**
     * 运行应用
     */
    public function run(): void
    {
        $this->bootstrap();

        try {
            // 创建请求
            $request = $this->request();
            
            // 预处理请求
            $request = $this->handleRequest($request);
            
            // 分发路由
            $response = $this->dispatch($request);
            
            // 后处理响应
            $response = $this->handleResponse($response);
            
            // 发送响应
            $response->send();
            
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * 处理请求
     */
    protected function handleRequest(Request $request): Request
    {
        // JSON请求体处理
        if ($request->isJson()) {
            $jsonData = $request->json();
            if ($jsonData !== null) {
                $request->merge($jsonData);
                // 将JSON数据合并到$_POST以便input()等函数可以访问
                $_POST = array_merge($_POST, $jsonData);
            }
        }
        
        return $request;
    }

    /**
     * 处理响应
     */
    protected function handleResponse(Response $response): Response
    {
        // 添加性能头
        $executionTime = (microtime(true) - $this->startTime) * 1000;
        
        $response->header([
            'X-Response-Time' => sprintf('%.2fms', $executionTime),
            'X-Powered-By' => 'PandaAPI',
        ]);
        
        return $response;
    }

    /**
     * 分发请求到路由
     */
    protected function dispatch(Request $request): Response
    {
        $method = $request->method();
        $uri = $request->uri();
        
        // 执行全局中间件
        $request = $this->runMiddleware($request);
        
        if ($request instanceof Response) {
            return $request;
        }
        
        try {
            // 分发到路由
            $result = Route::dispatch($method, $uri);
            
            // 处理返回值
            if ($result instanceof Response) {
                return $result;
            }
            
            // 如果是数组或标量，转为JSON响应
            if (is_array($result) || is_scalar($result) || $result === null) {
                return Response::json($result);
            }
            
            // 其他类型直接返回
            return Response::json(['data' => $result]);
            
        } catch (ApiException $e) {
            return Response::error($e->getMessage(), $e->getStatusCode(), $e->getData());
        }
    }

    /**
     * 执行全局中间件
     */
    /**
     * @return Request|Response
     */
    protected function runMiddleware(Request $request)
    {
        foreach ($this->middleware as $middleware) {
            $class = Middleware::resolve($middleware);
            
            if ($class === null) {
                continue;
            }
            
            $instance = new $class();
            
            if (method_exists($instance, 'handle')) {
                $result = $instance->handle($request, function ($req) {
                    return $req;
                });
                
                if ($result instanceof Response) {
                    return $result;
                }
                
                $request = $result;
            }
        }
        
        return $request;
    }

    /**
     * 处理异常
     */
    protected function handleException(\Throwable $e): void
    {
        $statusCode = 500;
        $message = 'Internal Server Error';
        
        if ($e instanceof ApiException) {
            $statusCode = $e->getStatusCode();
            $message = $e->getMessage();
        }
        
        // 在开发环境显示详细错误
        $debug = $this->config['debug'] ?? false;
        
        if ($debug) {
            $response = Response::json([
                'error' => true,
                'message' => $message,
                'code' => $statusCode,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], $statusCode);
        } else {
            $response = Response::json([
                'error' => true,
                'message' => $message,
                'code' => $statusCode,
            ], $statusCode);
        }
        
        $response->send();
    }

    /**
     * 获取性能统计
     */
    public function getStats(): array
    {
        $this->stats['time'] = (microtime(true) - $this->startTime) * 1000;
        $this->stats['memory'] = memory_get_peak_usage(true);
        
        // 获取数据库查询统计
        $dbInstance = \PandaAPI\Database\DB::getInstance();
        if ($dbInstance !== null) {
            $dbStats = \PandaAPI\Database\DB::getQueryStats();
            $this->stats['queries'] = $dbStats['count'] ?? 0;
            $this->stats['db_time'] = $dbStats['total_time'] ?? 0;
        }
        
        return $this->stats;
    }

    /**
     * 获取环境变量
     */
    public function env(string $key, $default = null)
    {
        return getenv($key) ?: $default;
    }

    /**
     * 检查是否为生产环境
     */
    public function isProduction(): bool
    {
        return $this->env('APP_ENV') === 'production';
    }

    /**
     * 检查是否为开发环境
     */
    public function isDevelopment(): bool
    {
        return $this->env('APP_ENV', 'development') === 'development';
    }

    /**
     * 获取配置项
     */
    public function getConfig(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->config;
        }
        
        return $this->config[$key] ?? $default;
    }
}
