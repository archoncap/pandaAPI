<?php
/**
 * 公共函数库
 * 
 * 全局可用的辅助函数，供整个项目调用
 * 使用方式：require_once __DIR__ . '/common/functions.php';
 */

use PandaAPI\Database\PandaDB;
use PandaAPI\Cache\Cache;

if (!function_exists('config')) {
    /**
     * 获取配置项
     * 
     * @param string $key 配置键名，支持点号分隔 如 'database.host'
     * @param mixed $default 默认值
     * @return mixed
     */
    function config(string $key, $default = null)
    {
        static $config = null;
        if ($config === null) {
            $configFile = dirname(__DIR__) . '/config/app.php';
            if (file_exists($configFile)) {
                $config = require $configFile;
            } else {
                $config = [];
            }
        }

        $keys = explode('.', $key);
        $value = $config;
        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}

if (!function_exists('json_success')) {
    /**
     * 返回成功 JSON 响应
     * 
     * @param mixed $data 数据
     * @param string $message 提示信息
     * @param int $code 状态码
     * @return array
     */
    function json_success($data = null, string $message = 'success', int $code = 200): array
    {
        return [
            'code'    => $code,
            'message' => $message,
            'data'    => $data,
        ];
    }
}

if (!function_exists('json_error')) {
    /**
     * 返回错误 JSON 响应
     * 
     * @param string $message 错误信息
     * @param int $code 错误码
     * @param mixed $data 附加数据
     * @return array
     */
    function json_error(string $message = 'error', int $code = 400, $data = null): array
    {
        return [
            'code'    => $code,
            'message' => $message,
            'data'    => $data,
        ];
    }
}

if (!function_exists('json_page')) {
    /**
     * 返回分页 JSON 响应
     * 
     * @param array $data 数据列表
     * @param int $total 总记录数
     * @param int $page 当前页码
     * @param int $perPage 每页条数
     * @return array
     */
    function json_page(array $data, int $total, int $page, int $perPage): array
    {
        return [
            'code'    => 200,
            'message' => 'success',
            'data'    => $data,
            'pagination' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int)ceil($total / max($perPage, 1)),
            ],
        ];
    }
}

if (!function_exists('get_input')) {
    /**
     * 获取输入参数（GET/POST/JSON）
     * 
     * @param string $key 参数名
     * @param mixed $default 默认值
     * @return mixed
     */
    function get_input(string $key = null, $default = null)
    {
        static $input = null;
        if ($input === null) {
            $input = $_GET;
            // 合并 POST 数据
            if (!empty($_POST)) {
                $input = array_merge($input, $_POST);
            }
            // 合并 JSON 请求体
            $raw = file_get_contents('php://input');
            if (!empty($raw)) {
                $json = json_decode($raw, true);
                if (is_array($json)) {
                    $input = array_merge($input, $json);
                }
            }
        }

        if ($key === null) {
            return $input;
        }
        return $input[$key] ?? $default;
    }
}

if (!function_exists('generate_token')) {
    /**
     * 生成随机 Token
     * 
     * @param int $length 长度
     * @return string
     */
    function generate_token(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }
}

if (!function_exists('password_encrypt')) {
    /**
     * 密码加密
     * 
     * @param string $password 明文密码
     * @return string
     */
    function password_encrypt(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}

if (!function_exists('password_check')) {
    /**
     * 验证密码
     * 
     * @param string $password 明文密码
     * @param string $hash 加密后的哈希
     * @return bool
     */
    function password_check(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}

if (!function_exists('current_user')) {
    /**
     * 获取当前登录用户
     * 
     * @return array|null
     */
    function current_user(): ?array
    {
        $token = get_header('Authorization');
        if (empty($token)) {
            return null;
        }
        // 去掉 Bearer 前缀
        $token = str_replace('Bearer ', '', $token);
        if (empty($token)) {
            return null;
        }
        return Cache::get("token:{$token}");
    }
}

if (!function_exists('get_header')) {
    /**
     * 获取请求头
     * 
     * @param string $key 头名称
     * @param string $default 默认值
     * @return string
     */
    function get_header(string $key, string $default = ''): string
    {
        $headerKey = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $_SERVER[$headerKey] ?? $default;
    }
}

if (!function_exists('get_client_ip')) {
    /**
     * 获取客户端 IP
     * 
     * @return string
     */
    function get_client_ip(): string
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

if (!function_exists('str_random')) {
    /**
     * 生成随机字符串
     * 
     * @param int $length 长度
     * @return string
     */
    function str_random(int $length = 16): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $str;
    }
}

if (!function_exists('format_date')) {
    /**
     * 格式化日期
     * 
     * @param string|int $datetime 日期时间
     * @param string $format 格式
     * @return string
     */
    function format_date($datetime = null, string $format = 'Y-m-d H:i:s'): string
    {
        if ($datetime === null) {
            return date($format);
        }
        if (is_numeric($datetime)) {
            return date($format, (int)$datetime);
        }
        return date($format, strtotime($datetime));
    }
}

if (!function_exists('dd')) {
    /**
     * 调试打印并终止
     * 
     * @param mixed ...$vars 变量
     */
    function dd(...$vars): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($vars, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('response_json')) {
    /**
     * 输出 JSON 响应并终止
     * 
     * @param array $data 响应数据
     * @param int $httpCode HTTP 状态码
     */
    function response_json(array $data, int $httpCode = 200): void
    {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('redirect')) {
    /**
     * URL 重定向
     * 
     * @param string $url 目标 URL
     * @param int $code 状态码
     */
    function redirect(string $url, int $code = 302): void
    {
        http_response_code($code);
        header("Location: {$url}");
        exit;
    }
}

if (!function_exists('array_get')) {
    /**
     * 用点号语法获取数组值
     * 
     * @param array $array 数组
     * @param string $key 键名 如 'user.name'
     * @param mixed $default 默认值
     * @return mixed
     */
    function array_get(array $array, string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $array;
        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}

if (!function_exists('array_only')) {
    /**
     * 从数组中提取指定键
     * 
     * @param array $array 原数组
     * @param array $keys 要提取的键
     * @return array
     */
    function array_only(array $array, array $keys): array
    {
        return array_intersect_key($array, array_flip($keys));
    }
}

if (!function_exists('array_except')) {
    /**
     * 从数组中排除指定键
     * 
     * @param array $array 原数组
     * @param array $keys 要排除的键
     * @return array
     */
    function array_except(array $array, array $keys): array
    {
        return array_diff_key($array, array_flip($keys));
    }
}

if (!function_exists('is_ajax')) {
    /**
     * 判断是否为 AJAX 请求
     * 
     * @return bool
     */
    function is_ajax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}

if (!function_exists('is_post')) {
    /**
     * 判断是否为 POST 请求
     * 
     * @return bool
     */
    function is_post(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
}

if (!function_exists('is_get')) {
    /**
     * 判断是否为 GET 请求
     * 
     * @return bool
     */
    function is_get(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }
}
