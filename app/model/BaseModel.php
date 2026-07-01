<?php
/**
 * 基础模型
 * 
 * 所有模型继承此类，获得 PandaDB 的链式操作能力
 */
namespace App\Model;

use PandaAPI\Database\PandaDB;

class BaseModel
{
    /** @var string 表名 */
    protected $table = '';

    /** @var string 主键 */
    protected $primaryKey = 'id';

    /** @var PandaDB 查询构建器实例 */
    protected $builder;

    public function __construct()
    {
        if (empty($this->table)) {
            $name = substr(strrchr(static::class, '\\'), 1);
            // UserModel -> user, PostModel -> post
            $this->table = strtolower(str_replace('Model', '', $name));
        }
        $this->builder = PandaDB::table($this->table);
    }

    /**
     * 查询单条（按主键）
     */
    public function find($id): ?array
    {
        return PandaDB::table($this->table)
            ->where($this->primaryKey, $id)
            ->find();
    }

    /**
     * 查询全部
     */
    public function all(): array
    {
        return PandaDB::table($this->table)->select();
    }

    /**
     * 条件查询
     */
    public function where($field, $value = null): self
    {
        $this->builder->where($field, $value);
        return $this;
    }

    /**
     * 排序
     */
    public function order(string $field, string $direction = 'ASC'): self
    {
        $this->builder->order($field, $direction);
        return $this;
    }

    /**
     * 限制条数
     */
    public function limit(int $limit): self
    {
        $this->builder->limit($limit);
        return $this;
    }

    /**
     * 执行查询
     */
    public function select(): array
    {
        return $this->builder->select();
    }

    /**
     * 分页查询
     */
    public function paginate(int $perPage = 15, int $page = null): array
    {
        return $this->builder->paginate($perPage, $page);
    }

    /**
     * 统计数量
     */
    public function count(): int
    {
        return $this->builder->count();
    }

    /**
     * 插入数据
     */
    public function insert(array $data): int
    {
        return PandaDB::table($this->table)->insert($data);
    }

    /**
     * 更新数据
     */
    public function update(array $data, array $where = []): int
    {
        return PandaDB::table($this->table)
            ->where($where)
            ->update($data);
    }

    /**
     * 删除数据
     */
    public function delete(array $where = []): int
    {
        return PandaDB::table($this->table)
            ->where($where)
            ->delete();
    }

    /**
     * 获取底层构建器（用于复杂查询）
     */
    public function getBuilder(): object
    {
        return $this->builder;
    }
}
