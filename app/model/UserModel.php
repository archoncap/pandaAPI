<?php
/**
 * 用户模型
 */
namespace App\Model;

class UserModel extends BaseModel
{
    protected $table = 'users';
    protected $primaryKey = 'id';

    /**
     * 根据邮箱查找用户
     */
    public function findByEmail(string $email): ?array
    {
        return PandaDB::table($this->table)
            ->where('email', $email)
            ->find();
    }

    /**
     * 创建用户
     */
    public function createUser(array $data): int
    {
        $data['password']   = password_encrypt($data['password']);
        $data['status']     = 1;
        $data['created_at'] = format_date();
        return $this->insert($data);
    }

    /**
     * 验证用户密码
     */
    public function verifyPassword(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);
        if (!$user || !password_check($password, $user['password'])) {
            return null;
        }
        return $user;
    }

    /**
     * 获取活跃用户列表
     */
    public function getActiveUsers(int $limit = 15, int $page = null): array
    {
        return PandaDB::table($this->table)
            ->where('status', 1)
            ->order('created_at', 'desc')
            ->paginate($limit, $page);
    }

    /**
     * 检查邮箱是否已存在
     */
    public function emailExists(string $email): bool
    {
        return PandaDB::table($this->table)
            ->where('email', $email)
            ->count() > 0;
    }
}
