<?php
/**
 * 健康检查控制器
 */
namespace App\Controller;

class HealthController extends BaseController
{
    /**
     * 健康检查
     * GET /api/v1/health
     */
    public function ping(): array
    {
        return $this->success([
            'framework'  => 'PandaAPI',
            'name'       => '熊猫API框架',
            'status'     => 'healthy',
            'time'       => format_date(),
            'version'    => '1.0.0',
        ]);
    }
}
