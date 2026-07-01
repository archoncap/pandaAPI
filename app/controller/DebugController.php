<?php
/**
 * 调试控制器（仅开发环境使用）
 */
namespace App\Controller;

use PandaAPI\Database\PandaDB;

class DebugController extends BaseController
{
    /**
     * 调试信息
     * GET /api/v1/private/debug
     */
    public function info(): array
    {
        return $this->success([
            'query_count'    => PandaDB::getQueryCount(),
            'total_time'     => PandaDB::getTotalQueryTime(),
            'slow_queries'   => PandaDB::getSlowQueries(),
            'query_log'      => PandaDB::getQueryLog(),
            'php_version'    => PHP_VERSION,
            'memory_usage'   => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            'client_ip'      => get_client_ip(),
        ]);
    }
}
