<?php
/**
 * 中间件管理器
 */

namespace PandaAPI\Route;

use PandaAPI\Exception\ApiException;

class Middleware
{
    /**
     * @var array 全局中间件
     */
    protected static $global = [];

    /**
     * @var array 路由中间件
     */
    protected static $routeMiddleware = [];

    /**
     * @var array 中间件别名
     */
    protected static $aliases = [];

    /**
     * 初始化默认中间件别名
     */
    public static function boot(): void
    {
        self::$aliases = [
            'auth' => \PandaAPI\Middleware\AuthMiddleware::class,
            'cors' => \PandaAPI\Middleware\CorsMiddleware::class,
            'log' => \PandaAPI\Middleware\LogMiddleware::class,
            'throttle' => \PandaAPI\Middleware\ThrottleMiddleware::class,
            'validate' => \PandaAPI\Middleware\ValidateMiddleware::class,
        ];
    }

    /**
     * 注册全局中间件
     */
    public static function global(array $middleware): void
    {
        self::$global = $middleware;
    }

    /**
     * 添加全局中间件
     */
    public static function addGlobal(string $middleware): void
    {
        self::$global[] = $middleware;
    }

    /**
     * 注册路由中间件
     */
    public static function register(string $name, string $class): void
    {
        self::$routeMiddleware[$name] = $class;
    }

    /**
     * 注册中间件别名
     */
    public static function alias(string $name, string $class): void
    {
        self::$aliases[$name] = $class;
    }

    /**
     * 获取中间件别名
     */
    public static function getAlias(string $name): ?string
    {
        return self::$aliases[$name] ?? null;
    }

    /**
     * 解析中间件
     */
    public static function resolve($middleware): ?string
    {
        if ($middleware instanceof \Closure) {
            return null;
        }

        if (is_string($middleware)) {
            // 检查别名
            if (isset(self::$aliases[$middleware])) {
                return self::$aliases[$middleware];
            }
            
            // 检查路由中间件
            if (isset(self::$routeMiddleware[$middleware])) {
                return self::$routeMiddleware[$middleware];
            }
            
            // 直接返回类名
            if (class_exists($middleware)) {
                return $middleware;
            }
        }

        return null;
    }

    /**
     * 执行中间件链
     */
    public static function handle(array $middlewareList, $request, callable $next)
    {
        if (empty($middlewareList)) {
            return $next($request);
        }

        $middleware = array_shift($middlewareList);
        $class = self::resolve($middleware);

        if ($class === null) {
            throw new ApiException("Middleware not found: {$middleware}");
        }

        $instance = new $class();

        if (method_exists($instance, 'handle')) {
            return $instance->handle($request, function ($req) use ($middlewareList, $next) {
                return self::handle($middlewareList, $req, $next);
            });
        }

        if (method_exists($instance, '__invoke')) {
            return $instance($request, function ($req) use ($middlewareList, $next) {
                return self::handle($middlewareList, $req, $next);
            });
        }

        throw new ApiException("Middleware must have handle() or __invoke() method");
    }

    /**
     * 获取全局中间件
     */
    public static function getGlobal(): array
    {
        return self::$global;
    }

    /**
     * 清空中间件
     */
    public static function clear(): void
    {
        self::$global = [];
        self::$routeMiddleware = [];
    }
}
