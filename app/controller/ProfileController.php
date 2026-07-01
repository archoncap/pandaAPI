<?php
/**
 * 个人中心控制器
 */
namespace App\Controller;

use PandaAPI\Database\PandaDB;

class ProfileController extends BaseController
{
    /**
     * 个人资料
     * GET /api/v1/private/profile
     */
    public function show(): array
    {
        $user = current_user();
        if (!$user) {
            return $this->error('未登录', 401);
        }

        $profile = PandaDB::table('users')
            ->where('id', $user['user_id'])
            ->find();

        unset($profile['password']);
        return $this->success($profile);
    }

    /**
     * 修改资料
     * PUT /api/v1/private/profile
     */
    public function update(): array
    {
        $user = current_user();
        if (!$user) {
            return $this->error('未登录', 401);
        }

        $data = $this->input();
        if (empty($data)) {
            return $this->error('没有要更新的数据', 400);
        }

        $data['updated_at'] = format_date();
        PandaDB::table('users')
            ->where('id', $user['user_id'])
            ->update($data);

        return $this->success(null, '更新成功');
    }
}
