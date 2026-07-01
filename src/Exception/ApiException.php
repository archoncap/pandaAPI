<?php
/**
 * 异常基类
 */

namespace PandaAPI\Exception;

class ApiException extends \Exception
{
    /**
     * @var mixed 额外数据
     */
    public $data;

    /**
     * @var array HTTP状态码映射
     */
    public static $codes = [
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        503 => 'Service Unavailable',
    ];

    /**
     * 构造函数
     */
    public function __construct(string $message = '', int $code = 500, $data = null, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }

    /**
     * 获取HTTP状态码
     */
    public function getStatusCode(): int
    {
        return $this->code;
    }

    /**
     * 获取错误数据
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * 转换为数组
     */
    public function toArray(): array
    {
        return [
            'error' => true,
            'message' => $this->getMessage(),
            'code' => $this->code,
            'data' => $this->data
        ];
    }
}
