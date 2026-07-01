<?php
/**
 * 缓存异常
 */

namespace PandaAPI\Exception;

class CacheException extends ApiException
{
    /**
     * 构造函数
     */
    public function __construct(string $message = '', int $code = 500, $data = null, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $data, $previous);
    }
}
