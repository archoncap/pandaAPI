<?php
/**
 * 请求类
 * 处理HTTP请求
 */

namespace PandaAPI\Core;

class Request
{
    /**
     * @var array 服务器变量
     */
    protected $server;

    /**
     * @var array GET参数
     */
    protected $query;

    /**
     * @var array POST参数
     */
    protected $post;

    /**
     * @var array 请求体
     */
    protected $body;

    /**
     * @var array 请求头
     */
    protected $headers;

    /**
     * @var array 路由参数
     */
    protected $params = [];

    /**
     * @var array 上传文件
     */
    protected $files;

    /**
     * @var array Cookies
     */
    protected $cookies;

    /**
     * @var mixed 当前用户
     */
    protected $user;

    /**
     * @var array 额外数据
     */
    protected $attributes = [];

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->server = $_SERVER;
        $this->query = $_GET;
        $this->post = $_POST;
        $this->files = $_FILES;
        $this->cookies = $_COOKIE;
        $this->headers = $this->parseHeaders();
        $this->body = file_get_contents('php://input');
    }

    /**
     * 解析请求头
     */
    protected function parseHeaders(): array
    {
        $headers = [];
        
        foreach ($this->server as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace('_', '-', substr($key, 5));
                $headers[$header] = $value;
            }
        }
        
        // 特殊处理Content-Type
        if (isset($this->server['CONTENT_TYPE'])) {
            $headers['CONTENT-TYPE'] = $this->server['CONTENT_TYPE'];
        }
        
        if (isset($this->server['CONTENT_LENGTH'])) {
            $headers['CONTENT-LENGTH'] = $this->server['CONTENT_LENGTH'];
        }
        
        return $headers;
    }

    /**
     * 获取HTTP方法
     */
    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * 获取请求URI
     */
    public function uri(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        return parse_url($uri, PHP_URL_PATH);
    }

    /**
     * 获取完整URL
     */
    public function url(): string
    {
        $scheme = $this->scheme();
        $host = $this->host();
        $uri = $this->uri();
        return $scheme . '://' . $host . $uri;
    }

    /**
     * 获取协议
     */
    public function scheme(): string
    {
        return $this->server['REQUEST_SCHEME'] ?? 'http';
    }

    /**
     * 获取主机名
     */
    public function host(): string
    {
        return $this->server['HTTP_HOST'] ?? $this->server['SERVER_NAME'] ?? 'localhost';
    }

    /**
     * 获取IP地址
     */
    public function ip(): string
    {
        $headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($headers as $header) {
            if (isset($this->server[$header])) {
                $ip = $this->server[$header];
                // 只取第一个IP
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }
        
        return '0.0.0.0';
    }

    /**
     * 获取GET参数
     */
    public function query(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    /**
     * 获取POST参数
     */
    public function post(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->post;
        }
        return $this->post[$key] ?? $default;
    }

    /**
     * 获取所有输入
     */
    public function all(): array
    {
        return array_merge($this->query, $this->post);
    }

    /**
     * 获取请求体
     */
    public function body(): string
    {
        return $this->body;
    }

    /**
     * 获取JSON数据
     */
    public function json(): ?array
    {
        $data = json_decode($this->body, true);
        return json_last_error() === JSON_ERROR_NONE ? $data : null;
    }

    /**
     * 获取请求头
     */
    public function header(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->headers;
        }
        
        $key = strtoupper(str_replace('-', '_', $key));
        return $this->headers[$key] ?? $default;
    }

    /**
     * 获取路由参数
     */
    public function route(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->params;
        }
        return $this->params[$key] ?? $default;
    }

    /**
     * 设置路由参数
     */
    public function setRouteParams(array $params): self
    {
        $this->params = $params;
        return $this;
    }

    /**
     * 获取上传文件
     */
    public function file(string $key = null)
    {
        if ($key === null) {
            return $this->files;
        }
        return $this->files[$key] ?? null;
    }

    /**
     * 获取Cookie
     */
    public function cookie(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->cookies;
        }
        return $this->cookies[$key] ?? $default;
    }

    /**
     * 获取用户
     */
    public function user()
    {
        return $this->user;
    }

    /**
     * 设置用户
     */
    public function setUser($user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * 获取属性
     */
    public function __get(string $key)
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * 设置属性
     */
    public function __set(string $key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * 检查属性
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    /**
     * 获取属性
     */
    public function get(string $key, $default = null)
    {
        return $this->attributes[$key] ?? $this->all()[$key] ?? $default;
    }

    /**
     * 设置属性
     */
    public function set(string $key, $value): self
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * 合并属性
     */
    public function merge(array $data): self
    {
        $this->attributes = array_merge($this->attributes, $data);
        return $this;
    }

    /**
     * 获取请求时间
     */
    public function time(): float
    {
        return (float)($this->server['REQUEST_TIME_FLOAT'] ?? microtime(true));
    }

    /**
     * 检查是否为AJAX请求
     */
    public function isAjax(): bool
    {
        return strtolower($this->header('X-Requested-With')) === 'xmlhttprequest';
    }

    /**
     * 检查是否为JSON请求
     */
    public function isJson(): bool
    {
        $contentType = $this->header('Content-Type', '');
        return strpos($contentType, 'application/json') !== false;
    }
}
