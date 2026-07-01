<?php
/**
 * 基础控制器
 * 
 * 所有控制器继承此类，获得公共方法
 */
namespace App\Controller;

use PandaAPI\Core\Request;
use PandaAPI\Core\Response;

class BaseController
{
    /**
     * 成功响应
     */
    protected function success($data = null, string $message = 'success'): array
    {
        return json_success($data, $message);
    }

    /**
     * 错误响应
     */
    protected function error(string $message = 'error', int $code = 400, $data = null): array
    {
        return json_error($message, $code, $data);
    }

    /**
     * 分页响应
     */
    protected function paginate(int $page, int $perPage, int $total, array $data): array
    {
        return json_page($data, $total, $page, $perPage);
    }

    /**
     * 获取输入参数
     */
    protected function input(string $key = null, $default = null)
    {
        return get_input($key, $default);
    }

    /**
     * 获取分页参数
     */
    protected function getPageParams(): array
    {
        $page    = (int)$this->input('page', 1);
        $perPage = (int)$this->input('per_page', 15);
        $page    = max(1, $page);
        $perPage = min(100, max(1, $perPage));
        return [$page, $perPage];
    }

    /**
     * 输出 JSON 并终止
     */
    protected function json(array $data, int $httpCode = 200): void
    {
        response_json($data, $httpCode);
    }
}
