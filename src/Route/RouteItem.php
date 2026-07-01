<?php
/**
 * 路由项类
 * 支持链式调用和中间件配置
 */

namespace PandaAPI\Route;

class RouteItem
{
    /**
     * @var string 路由路径
     */
    protected $path;

    /**
     * @var mixed 处理器
     */
    protected $handler;

    /**
     * @var array 中间件
     */
    protected $middleware = [];

    /**
     * @var string 路由名称
     */
    protected $name;

    /**
     * @var array 默认参数
     */
    protected $defaults = [];

    /**
     * @var array 约束条件
     */
    protected $wheres = [];

    /**
     * @var string 域名约束
     */
    protected $domain;

    /**
     * @var array 当前组属性
     */
    protected $groupOptions = [];

    /**
     * 构造函数
     */
    public function __construct(string $path, $handler)
    {
        $this->path = $path;
        $this->handler = $handler;
    }

    /**
     * 设置中间件
     */
    public function middleware($middleware): self
    {
        if (is_string($middleware)) {
            $this->middleware[] = $middleware;
        } elseif (is_array($middleware)) {
            $this->middleware = array_merge($this->middleware, $middleware);
        } elseif ($middleware instanceof \Closure) {
            $this->middleware[] = $middleware;
        }
        
        return $this;
    }

    /**
     * 添加中间件
     */
    public function addMiddleware($middleware): self
    {
        return $this->middleware($middleware);
    }

    /**
     * 设置路由名称
     */
    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * 设置默认值
     */
    public function defaults(array $defaults): self
    {
        $this->defaults = array_merge($this->defaults, $defaults);
        return $this;
    }

    /**
     * 设置参数约束
     * @param array|string $arg1 约束数组 或 参数名
     * @param string|null $arg2 正则模式(当$arg1为字符串时)
     */
    public function where($arg1, ?string $arg2 = null): self
    {
        if (is_array($arg1)) {
            $this->wheres = array_merge($this->wheres, $arg1);
        } elseif (is_string($arg1) && $arg2 !== null) {
            $this->wheres[$arg1] = $arg2;
        }
        return $this;
    }

    /**
     * 设置数字约束
     */
    public function whereNumber(string $param): self
    {
        $this->wheres[$param] = '[0-9]+';
        return $this;
    }

    /**
     * 设置字母约束
     */
    public function whereAlpha(string $param): self
    {
        $this->wheres[$param] = '[a-zA-Z]+';
        return $this;
    }

    /**
     * 设置字母数字约束
     */
    public function whereAlphaNum(string $param): self
    {
        $this->wheres[$param] = '[a-zA-Z0-9]+';
        return $this;
    }

    /**
     * 设置UUID约束
     */
    public function whereUuid(string $param): self
    {
        $this->wheres[$param] = '[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}';
        return $this;
    }

    /**
     * 设置域名约束
     */
    public function domain(string $domain): self
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * 设置组属性
     */
    public function setGroupOptions(array $options): self
    {
        $this->groupOptions = $options;
        return $this;
    }

    /**
     * 处理请求
     */
    public function handle(...$params)
    {
        // 执行中间件链
        if (!empty($this->middleware) || !empty($this->groupOptions['middleware'])) {
            $middlewareChain = array_merge(
                $this->groupOptions['middleware'] ?? [],
                $this->middleware
            );
            
            return $this->runMiddleware($middlewareChain, $params);
        }
        
        return $this->callHandler($params);
    }

    /**
     * 执行中间件链
     */
    protected function runMiddleware(array $middlewareChain, array $params)
    {
        if (empty($middlewareChain)) {
            return $this->callHandler($params);
        }

        $middleware = array_shift($middlewareChain);
        
        $next = function (...$args) use ($middlewareChain, $params) {
            return $this->runMiddleware($middlewareChain, $args);
        };

        if ($middleware instanceof \Closure) {
            return $middleware($params[0] ?? null, $next);
        }

        if (is_string($middleware) && class_exists($middleware)) {
            $instance = new $middleware();
            
            if (method_exists($instance, 'handle')) {
                return $instance->handle($params[0] ?? null, $next);
            }
        }

        return $this->callHandler($params);
    }

    /**
     * 调用处理器
     */
    protected function callHandler(array $params = [])
    {
        $handler = $this->handler;
        
        // 合并默认值
        $params = array_merge($this->defaults, $params);
        
        if ($handler instanceof \Closure) {
            return call_user_func_array($handler, $params);
        }
        
        if (is_string($handler)) {
            // 格式: "Controller@action"
            if (strpos($handler, '@') !== false) {
                [$controller, $action] = explode('@', $handler);
                
                if (class_exists($controller)) {
                    $controller = new $controller();
                    
                    if (method_exists($controller, $action)) {
                        return call_user_func_array([$controller, $action], $params);
                    }
                }
            }
            
            // 格式: "Controller"
            if (class_exists($handler)) {
                $controller = new $handler();
                
                // 调用默认方法或__invoke
                if (method_exists($controller, '__invoke')) {
                    return call_user_func_array($controller, $params);
                }
            }
        }
        
        if (is_array($handler) && count($handler) === 2) {
            [$controller, $action] = $handler;
            
            if (is_string($controller) && class_exists($controller)) {
                $controller = new $controller();
            }
            
            if (is_object($controller) && method_exists($controller, $action)) {
                return call_user_func_array([$controller, $action], $params);
            }
        }
        
        throw new \RuntimeException('Handler not callable: ' . print_r($this->handler, true));
    }

    /**
     * 获取路径
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * 获取处理器
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * 获取中间件
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * 获取名称
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * 获取默认值
     */
    public function getDefaults(): array
    {
        return $this->defaults;
    }

    /**
     * 获取约束
     */
    public function getWheres(): array
    {
        return $this->wheres;
    }

    /**
     * 获取域名
     */
    public function getDomain(): ?string
    {
        return $this->domain;
    }

    /**
     * 生成URL
     */
    public function url(array $params = []): string
    {
        $path = $this->path;
        
        foreach ($params as $key => $value) {
            $path = preg_replace("/\{{$key}\}/", $value, $path);
        }
        
        // 移除可选参数
        $path = preg_replace('/\/\{\w+\?\}/', '', $path);
        
        return $path;
    }
}
