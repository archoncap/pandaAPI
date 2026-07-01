<?php
/**
 * 响应类
 * 处理HTTP响应
 */

namespace PandaAPI\Core;

class Response
{
    /**
     * @var int HTTP状态码
     */
    protected $statusCode = 200;

    /**
     * @var array 响应头
     */
    protected $headers = [];

    /**
     * @var string 响应内容
     */
    protected $content = '';

    /**
     * @var string 内容类型
     */
    protected $contentType = 'application/json';

    /**
     * @var array 状态码文本
     */
    protected static $statusTexts = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        409 => 'Conflict',
        410 => 'Gone',
        415 => 'Unsupported Media Type',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
    ];

    /**
     * 构造函数
     */
    public function __construct($content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    /**
     * 创建JSON响应
     */
    public static function json($data, int $statusCode = 200, array $headers = []): Response
    {
        $headers['Content-Type'] = 'application/json; charset=utf-8';
        
        if (is_array($data) || is_object($data)) {
            $content = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            $content = $data;
        }
        
        return new self($content, $statusCode, $headers);
    }

    /**
     * 创建纯文本响应
     */
    public static function text(string $content, int $statusCode = 200, array $headers = []): Response
    {
        $headers['Content-Type'] = 'text/plain; charset=utf-8';
        return new self($content, $statusCode, $headers);
    }

    /**
     * 创建HTML响应
     */
    public static function html(string $content, int $statusCode = 200, array $headers = []): Response
    {
        $headers['Content-Type'] = 'text/html; charset=utf-8';
        return new self($content, $statusCode, $headers);
    }

    /**
     * 创建XML响应
     */
    public static function xml(string $content, int $statusCode = 200, array $headers = []): Response
    {
        $headers['Content-Type'] = 'application/xml; charset=utf-8';
        return new self($content, $statusCode, $headers);
    }

    /**
     * 创建重定向响应
     */
    public static function redirect(string $url, int $statusCode = 302): Response
    {
        return new self('', $statusCode, ['Location' => $url]);
    }

    /**
     * 创建下载响应
     */
    public static function download(string $file, string $name = null, array $headers = []): Response
    {
        if ($name === null) {
            $name = basename($file);
        }
        
        $headers['Content-Type'] = 'application/octet-stream';
        $headers['Content-Disposition'] = 'attachment; filename="' . $name . '"';
        $headers['Content-Length'] = filesize($file);
        
        return new self(file_get_contents($file), 200, $headers);
    }

    /**
     * 创建视图响应
     */
    public static function view(string $template, array $data = []): Response
    {
        // 简单的模板引擎
        extract($data);
        
        ob_start();
        $templateFile = $template;
        if (strpos($template, '.php') === false) {
            $templateFile .= '.php';
        }
        
        if (file_exists($templateFile)) {
            include $templateFile;
        }
        
        $content = ob_get_clean();
        
        return self::html($content);
    }

    /**
     * 创建空响应
     */
    public static function noContent(int $statusCode = 204): Response
    {
        return new self('', $statusCode);
    }

    /**
     * 创建错误响应
     */
    public static function error(string $message, int $statusCode = 500, array $data = []): Response
    {
        $response = [
            'error' => true,
            'message' => $message,
            'status' => $statusCode,
        ];
        
        if (!empty($data)) {
            $response['data'] = $data;
        }
        
        return self::json($response, $statusCode);
    }

    /**
     * 创建成功响应
     */
    public static function success($data = null, string $message = 'OK'): Response
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return self::json($response);
    }

    /**
     * 创建分页响应
     */
    public static function paginate(array $data, int $total, int $page, int $perPage): Response
    {
        $totalPages = ceil($total / $perPage);
        
        $response = [
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $totalPages,
                'from' => ($page - 1) * $perPage + 1,
                'to' => min($page * $perPage, $total),
            ]
        ];
        
        return self::json($response)->header([
            'X-Total-Count' => $total,
            'X-Page-Count' => $totalPages
        ]);
    }

    /**
     * 发送响应
     */
    public function send(): void
    {
        // 设置状态码
        http_response_code($this->statusCode);
        
        // 发送头
        foreach ($this->headers as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    header("{$key}: {$v}");
                }
            } else {
                header("{$key}: {$value}");
            }
        }
        
        // 发送内容
        echo $this->content;
    }

    /**
     * 获取状态码
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * 设置状态码
     */
    public function status(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * 获取响应头
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * 设置响应头
     */
    public function header($key, $value = null): self
    {
        if (is_array($key)) {
            $this->headers = array_merge($this->headers, $key);
        } else {
            $this->headers[$key] = $value;
        }
        return $this;
    }

    /**
     * 获取内容
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * 设置内容
     */
    public function setContent($content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * 获取内容类型
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * 设置内容类型
     */
    public function setContentType(string $contentType): self
    {
        $this->contentType = $contentType;
        $this->headers['Content-Type'] = $contentType;
        return $this;
    }

    /**
     * 获取状态文本
     */
    public function getStatusText(): string
    {
        return self::$statusTexts[$this->statusCode] ?? 'Unknown';
    }

    /**
     * 检查是否成功
     */
    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * 检查是否为重定向
     */
    public function isRedirect(): bool
    {
        return in_array($this->statusCode, [301, 302, 303, 307, 308]);
    }

    /**
     * 检查是否有错误
     */
    public function isError(): bool
    {
        return $this->statusCode >= 400;
    }

    /**
     * 添加Cookie
     */
    public function cookie(string $name, string $value, int $expire = 0, string $path = '/', 
        string $domain = '', bool $secure = false, bool $httponly = true): self
    {
        setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
        return $this;
    }

    /**
     * 转换为字符串
     */
    public function __toString(): string
    {
        return $this->content;
    }
}
