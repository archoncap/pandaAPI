<?php
/**
 * 数据库异常
 */

namespace PandaAPI\Exception;

class DbException extends ApiException
{
    /**
     * 构造函数
     */
    public function __construct(string $message = '', int $code = 500, $data = null, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $data, $previous);
    }
}
