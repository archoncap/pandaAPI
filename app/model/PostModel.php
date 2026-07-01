<?php
/**
 * 文章模型
 */
namespace App\Model;

use PandaAPI\Database\PandaDB;

class PostModel extends BaseModel
{
    protected $table = 'posts';
    protected $primaryKey = 'id';

    /**
     * 获取文章列表（关联用户表）
     */
    public function getList(int $perPage = 10, int $page = null): array
    {
        return PandaDB::table($this->table)
            ->field('posts.*, users.name as author_name')
            ->join('users', 'posts.user_id = users.id')
            ->where('posts.status', 1)
            ->order('posts.created_at', 'desc')
            ->paginate($perPage, $page);
    }

    /**
     * 获取文章详情（关联用户）
     */
    public function getDetail(int $id): ?array
    {
        return PandaDB::table($this->table)
            ->field('posts.*, users.name as author_name')
            ->join('users', 'posts.user_id = users.id')
            ->where('posts.id', $id)
            ->find();
    }

    /**
     * 增加浏览量
     */
    public function incrementViews(int $id): void
    {
        $post = $this->find($id);
        if ($post) {
            PandaDB::table($this->table)
                ->where('id', $id)
                ->update(['views' => $post['views'] + 1]);
        }
    }

    /**
     * 获取用户的所有文章
     */
    public function getByUserId(int $userId, int $perPage = 10): array
    {
        return PandaDB::table($this->table)
            ->where('user_id', $userId)
            ->order('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * 获取热门文章
     */
    public function getHot(int $limit = 10): array
    {
        return PandaDB::table($this->table)
            ->where('status', 1)
            ->order('views', 'desc')
            ->limit($limit)
            ->select();
    }

    /**
     * 获取统计信息
     */
    public function getStats(): array
    {
        return [
            'total'     => PandaDB::table($this->table)->count(),
            'total_views' => PandaDB::table($this->table)->sum('views'),
            'avg_views' => round(PandaDB::table($this->table)->avg('views'), 2),
        ];
    }
}
