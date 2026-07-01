<?php
/**
 * 路由核心类
 * 融合 inhere/sroute 的高效路由匹配
 */

namespace PandaAPI\Route;

use Inhere\Route\Base\RouterInterface;
use Inhere\Route\ORouter;
use PandaAPI\Exception\ApiException;

class Route
{
    /**
     * @var ORouter SRoute路由器
     */
    protected static $router;

    /**
     * @var array 全局中间件
     */
    protected static $globalMiddleware = [];

    /**
     * @var array 路由组
     */
    protected static $groups = [];

    /**
     * @var array 命名路由
     */
    protected static $namedRoutes = [];

    /**
     * @var array 当前匹配的路由信息
     */
    protected static $currentRoute = [];

    /**
     * 禁止实例化
     */
    private function __construct()
    {
    }

    /**
     * 初始化路由器
     */
    public static function init(array $options = []): void
    {
        self::$router = new ORouter([
            'autoRoute' => $options['autoRoute'] ?? false,
            'routes' => $options['routes'] ?? [],
        ]);
    }

    /**
     * 获取路由器
     */
    public static function getRouter(): ORouter
    {
        if (self::$router === null) {
            self::init();
        }
        return self::$router;
    }

    /**
     * 注册GET路由
     */
    public static function get(string $route, $handler): RouteItem
    {
        return self::addRoute('GET', $route, $handler);
    }

    /**
     * 注册POST路由
     */
    public static function post(string $route, $handler): RouteItem
    {
        return self::addRoute('POST', $route, $handler);
    }

    /**
     * 注册PUT路由
     */
    public static function put(string $route, $handler): RouteItem
    {
        return self::addRoute('PUT', $route, $handler);
    }

    /**
     * 注册PATCH路由
     */
    public static function patch(string $route, $handler): RouteItem
    {
        return self::addRoute('PATCH', $route, $handler);
    }

    /**
     * 注册DELETE路由
     */
    public static function delete(string $route, $handler): RouteItem
    {
        return self::addRoute('DELETE', $route, $handler);
    }

    /**
     * 注册OPTIONS路由
     */
    public static function options(string $route, $handler): RouteItem
    {
        return self::addRoute('OPTIONS', $route, $handler);
    }

    /**
     * 注册任意HTTP方法的路由
     */
    public static function any(string $route, $handler): RouteItem
    {
        return self::addRoute(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $route, $handler);
    }

    /**
     * 注册多个HTTP方法的路由
     */
    public static function match(array $methods, string $route, $handler): RouteItem
    {
        return self::addRoute($methods, $route, $handler);
    }

    /**
     * 添加路由
     */
    public static function addRoute($methods, string $route, $handler): RouteItem
    {
        $router = self::getRouter();
        
        // 应用当前组的属性
        $groupOptions = self::getCurrentGroupOptions();
        
        // 构建完整路径
        $fullPath = self::buildFullPath($route);
        
        // 创建路由项
        $item = new RouteItem($fullPath, $handler);
        
        // 应用中间件
        if (!empty($groupOptions['middleware'])) {
            $item->middleware($groupOptions['middleware']);
        }
        
        // 注册到路由器
        $methods = is_array($methods) ? $methods : [$methods];
        
        foreach ($methods as $method) {
            $router->map($method, $fullPath, [$item, 'handle']);
        }
        
        return $item;
    }

    /**
     * 创建路由组
     */
    public static function group(array $attributes, callable $callback): void
    {
        // 保存当前组
        self::$groups[] = $attributes;
        
        // 执行回调
        $callback();
        
        // 恢复上一个组
        array_pop(self::$groups);
    }

    /**
     * 获取当前组的选项
     */
    protected static function getCurrentGroupOptions(): array
    {
        if (empty(self::$groups)) {
            return [];
        }
        
        return end(self::$groups);
    }

    /**
     * 构建完整路径（包含组前缀）
     */
    protected static function buildFullPath(string $route): string
    {
        $prefix = '';
        
        foreach (self::$groups as $group) {
            if (isset($group['prefix'])) {
                $prefix .= rtrim($group['prefix'], '/');
            }
        }
        
        return $prefix . '/' . ltrim($route, '/');
    }

    /**
     * 设置控制器目录
     */
    public static function setControllerPath(string $path): void
    {
        $router = self::getRouter();
        $router->controllerNamespace = $path;
    }

    /**
     * 设置全局中间件
     */
    public static function middleware(array $middleware): void
    {
        self::$globalMiddleware = array_merge(self::$globalMiddleware, $middleware);
    }

    /**
     * 添加全局中间件
     */
    public static function addMiddleware(string $middleware): void
    {
        self::$globalMiddleware[] = $middleware;
    }

    /**
     * 获取全局中间件
     */
    public static function getMiddleware(): array
    {
        return self::$globalMiddleware;
    }

    /**
     * 添加命名路由
     */
    public static function name(string $name): RouteItem
    {
        $lastRoute = end(self::$currentRoute);
        if ($lastRoute) {
            self::$namedRoutes[$name] = $lastRoute->getPath();
        }
        return $lastRoute;
    }

    /**
     * 获取命名路由的URL
     */
    public static function route(string $name, array $params = []): string
    {
        if (!isset(self::$namedRoutes[$name])) {
            throw new ApiException("Route '{$name}' not found", 500);
        }
        
        $path = self::$namedRoutes[$name];
        
        // 替换路径参数
        foreach ($params as $key => $value) {
            $path = preg_replace("/\{{$key}\}/", $value, $path);
        }
        
        // 移除未替换的参数
        $path = preg_replace('/\/\{\w+\}/', '', $path);
        
        return $path;
    }

    /**
     * 分发请求
     */
    public static function dispatch(string $method, string $uri)
    {
        $router = self::getRouter();
        
        // 移除查询参数
        $uri = strtok($uri, '?');
        
        // 匹配路由 (ORouter returns [status, path, routeData])
        $result = $router->match($uri, $method);
        
        $status = $result[0] ?? \Inhere\Route\Base\RouterInterface::NOT_FOUND;
        $matchedPath = $result[1] ?? '';
        $routeData = $result[2] ?? null;
        
        if ($status === \Inhere\Route\Base\RouterInterface::NOT_FOUND) {
            throw new ApiException('Route not found', 404);
        }
        
        if ($status === \Inhere\Route\Base\RouterInterface::METHOD_NOT_ALLOWED) {
            throw new ApiException('Method not allowed', 405);
        }
        
        // 获取处理器和路径参数
        $handler = $routeData['handler'] ?? null;
        $params = $routeData['matches'] ?? [];
        
        if ($handler === null) {
            throw new ApiException('Route handler not found', 500);
        }
        
        // 合并到全局变量
        self::$currentRoute = $routeData;
        
        // 执行处理器
        if ($handler instanceof \Closure) {
            return call_user_func_array($handler, $params);
        }
        
        if (is_array($handler) && count($handler) === 2) {
            [$controller, $action] = $handler;
            
            if (is_string($controller) && strpos($controller, '@') !== false) {
                [$controllerClass, $action] = explode('@', $controller);
                $controller = new $controllerClass();
            }
            
            if (method_exists($controller, $action)) {
                return call_user_func_array([$controller, $action], $params);
            }
        }
        
        throw new ApiException('Handler not callable', 500);
    }

    /**
     * 获取当前路由信息
     */
    public static function getCurrentRoute(): array
    {
        return self::$currentRoute;
    }

    /**
     * 获取所有路由
     */
    public static function getRoutes(): array
    {
        return self::getRouter()->getStaticRoutes();
    }

    /**
     * 资源路由
     */
    public static function resource(string $name, string $controller): void
    {
        self::group(['prefix' => $name], function () use ($controller) {
            self::get('/', [$controller, 'index']);
            self::get('/{id}', [$controller, 'show']);
            self::post('/', [$controller, 'store']);
            self::put('/{id}', [$controller, 'update']);
            self::delete('/{id}', [$controller, 'destroy']);
        });
    }

    /**
     * 重定向路由
     */
    public static function redirect(string $from, string $to, int $status = 302): void
    {
        self::get($from, function () use ($to, $status) {
            return \PandaAPI\Core\Response::redirect($to, $status);
        });
    }

    /**
     * 视图路由
     */
    public static function view(string $route, string $template, array $data = []): void
    {
        self::get($route, function () use ($template, $data) {
            return \PandaAPI\Core\Response::view($template, $data);
        });
    }

    /**
     * 设置404处理器
     */
    public static function set404($handler): void
    {
        self::any('{path}', function ($path) use ($handler) {
            if ($handler instanceof \Closure) {
                return call_user_func($handler, $path);
            }
            return \PandaAPI\Core\Response::json(['error' => 'Not Found'], 404);
        });
    }

    /**
     * 设置错误处理器
     */
    public static function setErrorHandler($handler): void
    {
        // 可以设置全局错误处理
    }

    /**
     * 检查路由是否存在
     */
    public static function has(string $name): bool
    {
        return isset(self::$namedRoutes[$name]);
    }
}
