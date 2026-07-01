<?php
/**
 * 查询构建器
 * 支持链式调用，类似ThinkPHP5风格
 */

namespace QuickAPI\Database;

use PDO;
use PDOStatement;
use QuickAPI\Exception\DbException;

class Builder
{
    /**
     * @var Connection 数据库连接
     */
    protected $connection;

    /**
     * @var string 表名
     */
    protected $table;

    /**
     * @var array 查询数据
     */
    protected $data = [];

    /**
     * @var array WHERE条件
     */
    protected $where = [];

    /**
     * @var array JOIN语句
     */
    protected $join = [];

    /**
     * @var array ORDER BY
     */
    protected $orderBy = [];

    /**
     * @var array GROUP BY
     */
    protected $groupBy = [];

    /**
     * @var array HAVING
     */
    protected $having = [];

    /**
     * @var string|array 查询字段
     */
    protected $field = '*';

    /**
     * @var int|null LIMIT
     */
    protected $limit = null;

    /**
     * @var int|null OFFSET
     */
    protected $offset = null;

    /**
     * @var bool 是否使用连接池
     */
    protected $usePool = true;

    /**
     * @var string|array|null UNION查询
     */
    protected $union = null;

    /**
     * @var array 查询参数
     */
    protected $bindings = [];

    /**
     * @var bool 是否已执行
     */
    protected $executed = false;

    /**
     * @var int 最后插入ID
     */
    protected $lastInsertId;

    /**
     * @var int 影响行数
     */
    protected $affectedRows;

    /**
     * @var float 执行时间(ms)
     */
    protected $executionTime;

    /**
     * 构造函数
     */
    public function __construct(Connection $connection, string $table)
    {
        $this->connection = $connection;
        $this->table = $table;
    }

    /**
     * 设置表名
     */
    public function table(string $table): self
    {
        $this->table = $table;
        $this->reset();
        return $this;
    }

    /**
     * 设置查询字段
     */
    public function field($field): self
    {
        if (is_string($field)) {
            $this->field = $field;
        } elseif (is_array($field)) {
            $this->field = implode(', ', $field);
        }
        return $this;
    }

    /**
     * WHERE条件
     */
    public function where($field, $operator = null, $value = null): self
    {
        if (is_array($field)) {
            foreach ($field as $k => $v) {
                $this->addWhere($k, '=', $v, 'AND');
            }
        } else {
            if ($value === null) {
                $value = $operator;
                $operator = '=';
            }
            $this->addWhere($field, $operator, $value, 'AND');
        }
        return $this;
    }

    /**
     * OR WHERE条件
     */
    public function whereOr($field, $operator = null, $value = null): self
    {
        if (is_array($field)) {
            foreach ($field as $k => $v) {
                $this->addWhere($k, '=', $v, 'OR');
            }
        } else {
            if ($value === null) {
                $value = $operator;
                $operator = '=';
            }
            $this->addWhere($field, $operator, $value, 'OR');
        }
        return $this;
    }

    /**
     * WHERE IN条件
     */
    public function whereIn(string $field, array $values): self
    {
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->where[] = [
            'field' => $field,
            'operator' => 'IN',
            'value' => "({$placeholders})",
            'bindings' => $values,
            'logic' => 'AND'
        ];
        return $this;
    }

    /**
     * WHERE NOT IN条件
     */
    public function whereNotIn(string $field, array $values): self
    {
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->where[] = [
            'field' => $field,
            'operator' => 'NOT IN',
            'value' => "({$placeholders})",
            'bindings' => $values,
            'logic' => 'AND'
        ];
        return $this;
    }

    /**
     * WHERE NULL条件
     */
    public function whereNull(string $field): self
    {
        $this->where[] = [
            'field' => $field,
            'operator' => 'IS NULL',
            'value' => '',
            'bindings' => [],
            'logic' => 'AND'
        ];
        return $this;
    }

    /**
     * WHERE NOT NULL条件
     */
    public function whereNotNull(string $field): self
    {
        $this->where[] = [
            'field' => $field,
            'operator' => 'IS NOT NULL',
            'value' => '',
            'bindings' => [],
            'logic' => 'AND'
        ];
        return $this;
    }

    /**
     * WHERE BETWEEN条件
     */
    public function whereBetween(string $field, $min, $max): self
    {
        $this->where[] = [
            'field' => $field,
            'operator' => 'BETWEEN',
            'value' => '? AND ?',
            'bindings' => [$min, $max],
            'logic' => 'AND'
        ];
        return $this;
    }

    /**
     * WHERE LIKE条件
     */
    public function whereLike(string $field, string $value, string $type = 'both'): self
    {
        $likeValue = $this->buildLikeValue($value, $type);
        return $this->where($field, 'LIKE', $likeValue);
    }

    /**
     * JOIN语句
     */
    public function join(string $table, string $on, string $type = 'INNER'): self
    {
        $this->join[] = [
            'table' => $table,
            'on' => $on,
            'type' => strtoupper($type)
        ];
        return $this;
    }

    /**
     * LEFT JOIN
     */
    public function leftJoin(string $table, string $on): self
    {
        return $this->join($table, $on, 'LEFT');
    }

    /**
     * RIGHT JOIN
     */
    public function rightJoin(string $table, string $on): self
    {
        return $this->join($table, $on, 'RIGHT');
    }

    /**
     * ORDER BY
     */
    public function order($field, string $direction = 'ASC'): self
    {
        if (is_string($field) && strpos($field, ',') !== false) {
            // 多个字段用逗号分隔
            $fields = explode(',', $field);
            foreach ($fields as $f) {
                $f = trim($f);
                if (preg_match('/^(\w+)\s+(ASC|DESC)$/i', $f, $matches)) {
                    $this->orderBy[] = [$matches[1], strtoupper($matches[2])];
                } else {
                    $this->orderBy[] = [$f, strtoupper($direction)];
                }
            }
        } else {
            $this->orderBy[] = [$field, strtoupper($direction)];
        }
        return $this;
    }

    /**
     * GROUP BY
     */
    public function group($field): self
    {
        if (is_string($field)) {
            $field = explode(',', $field);
        }
        $this->groupBy = array_merge($this->groupBy, $field);
        return $this;
    }

    /**
     * HAVING
     */
    public function having(string $condition, array $bindings = []): self
    {
        $this->having = [
            'condition' => $condition,
            'bindings' => $bindings
        ];
        return $this;
    }

    /**
     * LIMIT
     */
    public function limit(int $limit, int $offset = null): self
    {
        $this->limit = $limit;
        if ($offset !== null) {
            $this->offset = $offset;
        }
        return $this;
    }

    /**
     * OFFSET
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * PAGE (简化分页)
     */
    public function page(int $page, int $perPage = 15): self
    {
        $this->limit = $perPage;
        $this->offset = ($page - 1) * $perPage;
        return $this;
    }

    /**
     * 获取查询SQL
     */
    public function buildSelect(): array
    {
        $this->bindings = [];
        $sql = "SELECT {$this->field} FROM {$this->table}";
        
        // JOIN
        foreach ($this->join as $j) {
            $sql .= " {$j['type']} JOIN {$j['table']} ON {$j['on']}";
        }
        
        // WHERE
        if (!empty($this->where)) {
            $sql .= $this->buildWhere();
        }
        
        // GROUP BY
        if (!empty($this->groupBy)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupBy);
        }
        
        // HAVING
        if (!empty($this->having)) {
            $sql .= ' HAVING ' . $this->having['condition'];
            $this->bindings = array_merge($this->bindings, $this->having['bindings']);
        }
        
        // ORDER BY
        if (!empty($this->orderBy)) {
            $orders = [];
            foreach ($this->orderBy as $order) {
                $orders[] = "{$order[0]} {$order[1]}";
            }
            $sql .= ' ORDER BY ' . implode(', ', $orders);
        }
        
        // LIMIT & OFFSET
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
            if ($this->offset !== null) {
                $sql .= " OFFSET {$this->offset}";
            }
        }

        return ['sql' => $sql, 'bindings' => $this->bindings];
    }

    /**
     * 执行查询
     */
    public function select(): array
    {
        $startTime = microtime(true);
        
        $query = $this->buildSelect();
        $result = $this->execute($query['sql'], $query['bindings']);
        
        $this->executionTime = (microtime(true) - $startTime) * 1000;
        $this->executed = true;
        
        // 记录到性能统计
        $this->connection->recordQuery($query['sql'], $this->executionTime);
        
        return $result->fetchAll();
    }

    /**
     * 查询一条记录
     */
    public function find(): ?array
    {
        $this->limit(1);
        
        $startTime = microtime(true);
        $query = $this->buildSelect();
        $result = $this->execute($query['sql'], $query['bindings']);
        
        $this->executionTime = (microtime(true) - $startTime) * 1000;
        $this->executed = true;
        
        $this->connection->recordQuery($query['sql'], $this->executionTime);
        
        $row = $result->fetch();
        return $row ?: null;
    }

    /**
     * 获取一列值
     */
    public function column(string $column = null): ?string
    {
        if ($column !== null) {
            $this->field = $column;
        }
        
        $this->limit(1);
        $startTime = microtime(true);
        
        $query = $this->buildSelect();
        $result = $this->execute($query['sql'], $query['bindings']);
        
        $this->executionTime = (microtime(true) - $startTime) * 1000;
        $this->executed = true;
        
        $this->connection->recordQuery($query['sql'], $this->executionTime);
        
        $row = $result->fetch();
        return $row ? ($column !== null ? $row[$column] : reset($row)) : null;
    }

    /**
     * 获取所有值
     */
    public function columns(string $column = null): array
    {
        if ($column !== null) {
            $this->field = $column;
        }
        
        $startTime = microtime(true);
        $query = $this->buildSelect();
        $result = $this->execute($query['sql'], $query['bindings']);
        
        $this->executionTime = (microtime(true) - $startTime) * 1000;
        $this->executed = true;
        
        $this->connection->recordQuery($query['sql'], $this->executionTime);
        
        $rows = $result->fetchAll();
        if ($column !== null) {
            return array_column($rows, $column);
        }
        return array_map('reset', $rows);
    }

    /**
     * 插入数据
     */
    public function insert(array $data): int
    {
        $this->data = $data;
        
        $startTime = microtime(true);
        
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        
        $this->bindings = array_values($data);
        $result = $this->execute($sql, $this->bindings);
        
        $this->executionTime = (microtime(true) - $startTime) * 1000;
        $this->executed = true;
        $this->affectedRows = $result->rowCount();
        $this->lastInsertId = (int)$this->connection->getPdo()->lastInsertId();
        
        $this->connection->recordQuery($sql, $this->executionTime);
        
        return $this->lastInsertId;
    }

    /**
     * 批量插入
     */
    public function insertAll(array $dataSet): int
    {
        if (empty($dataSet)) {
            return 0;
        }
        
        $startTime = microtime(true);
        
        $columns = implode(', ', array_keys($dataSet[0]));
        $placeholders = implode(', ', array_fill(0, count($dataSet[0]), '?'));
        $values = [];
        $this->bindings = [];
        
        foreach ($dataSet as $data) {
            $values[] = "({$placeholders})";
            $this->bindings = array_merge($this->bindings, array_values($data));
        }
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES " . implode(', ', $values);
        
        $result = $this->execute($sql, $this->bindings);
        
        $this->executionTime = (microtime(true) - $startTime) * 1000;
        $this->executed = true;
        $this->affectedRows = $result->rowCount();
        
        $this->connection->recordQuery($sql, $this->executionTime);
        
        return $this->affectedRows;
    }

    /**
     * 更新数据
     */
    public function update(array $data): int
    {
        $this->data = $data;
        
        $startTime = microtime(true);
        
        $sets = [];
        $this->bindings = [];
        
        foreach ($data as $key => $value) {
            $sets[] = "{$key} = ?";
            $this->bindings[] = $value;
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets);
        
        // WHERE条件
        if (!empty($this->where)) {
            $sql .= $this->buildWhere();
        }
        
        $result = $this->execute($sql, $this->bindings);
        
        $this->executionTime = (microtime(true) - $startTime) * 1000;
        $this->executed = true;
        $this->affectedRows = $result->rowCount();
        
        $this->connection->recordQuery($sql, $this->executionTime);
        
        return $this->affectedRows;
    }

    /**
     * 删除数据
     */
    public function delete(): int
    {
        $startTime = microtime(true);
        
        $sql = "DELETE FROM {$this->table}";
        
        // WHERE条件
        if (!empty($this->where)) {
            $sql .= $this->buildWhere();
        }
        
        $result = $this->execute($sql, $this->bindings);
        
        $this->executionTime = (microtime(true) - $startTime) * 1000;
        $this->executed = true;
        $this->affectedRows = $result->rowCount();
        
        $this->connection->recordQuery($sql, $this->executionTime);
        
        return $this->affectedRows;
    }

    /**
     * 统计数量
     */
    public function count(string $field = '*'): int
    {
        $originalField = $this->field;
        $this->field = "COUNT({$field}) as __count";
        
        $result = $this->find();
        
        $this->field = $originalField;
        
        return (int)($result['__count'] ?? 0);
    }

    /**
     * 求和
     */
    public function sum(string $field): float
    {
        $originalField = $this->field;
        $this->field = "SUM({$field}) as __sum";
        
        $result = $this->find();
        
        $this->field = $originalField;
        
        return (float)($result['__sum'] ?? 0);
    }

    /**
     * 平均值
     */
    public function avg(string $field): float
    {
        $originalField = $this->field;
        $this->field = "AVG({$field}) as __avg";
        
        $result = $this->find();
        
        $this->field = $originalField;
        
        return (float)($result['__avg'] ?? 0);
    }

    /**
     * 最大值
     */
    public function max(string $field): float
    {
        $originalField = $this->field;
        $this->field = "MAX({$field}) as __max";
        
        $result = $this->find();
        
        $this->field = $originalField;
        
        return (float)($result['__max'] ?? 0);
    }

    /**
     * 最小值
     */
    public function min(string $field): float
    {
        $originalField = $this->field;
        $this->field = "MIN({$field}) as __min";
        
        $result = $this->find();
        
        $this->field = $originalField;
        
        return (float)($result['__min'] ?? 0);
    }

    /**
     * 分页查询
     */
    public function paginate(int $perPage = 15, int $currentPage = null): array
    {
        $currentPage = $currentPage ?? ($_GET['page'] ?? 1);
        $currentPage = max(1, (int)$currentPage);
        
        // 计算总数
        $total = $this->count();
        
        // 计算分页数据
        $totalPages = ceil($total / $perPage);
        $offset = ($currentPage - 1) * $perPage;
        
        // 查询当前页数据
        $this->limit($perPage, $offset);
        $items = $this->select();
        
        return [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $currentPage,
            'last_page' => $totalPages,
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total),
            'data' => $items
        ];
    }

    /**
     * 执行SQL
     */
    public function query(string $sql, array $bindings = []): PDOStatement
    {
        return $this->execute($sql, $bindings);
    }

    /**
     * 执行预处理语句
     */
    protected function execute(string $sql, array $bindings = []): PDOStatement
    {
        try {
            $pdo = $this->connection->getPdo();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($bindings);
            return $stmt;
        } catch (\PDOException $e) {
            throw new DbException("Query execution failed: " . $e->getMessage(), 500, $e);
        }
    }

    /**
     * 构建WHERE子句
     */
    protected function buildWhere(): string
    {
        $conditions = [];
        
        foreach ($this->where as $idx => $condition) {
            $field = $condition['field'];
            $operator = $condition['operator'];
            $value = $condition['value'];
            $logic = $condition['logic'];
            $bindings = $condition['bindings'] ?? [];
            
            if ($operator === 'IN' || $operator === 'NOT IN') {
                $cond = "{$field} {$operator} {$value}";
                $this->bindings = array_merge($this->bindings, $bindings);
            } elseif (in_array($operator, ['IS NULL', 'IS NOT NULL'])) {
                $cond = "{$field} {$operator}";
            } else {
                $cond = "{$field} {$operator} ?";
                $this->bindings[] = $value;
            }
            
            if ($idx === 0) {
                $conditions[] = $cond;
            } else {
                $conditions[] = "{$logic} {$cond}";
            }
        }
        
        return ' WHERE ' . implode(' ', $conditions);
    }

    /**
     * 添加WHERE条件
     */
    protected function addWhere($field, $operator, $value, $logic): void
    {
        $this->where[] = [
            'field' => $field,
            'operator' => strtoupper($operator),
            'value' => $value,
            'bindings' => [],
            'logic' => $logic
        ];
    }

    /**
     * 构建LIKE值
     */
    protected function buildLikeValue(string $value, string $type): string
    {
        switch ($type) {
            case 'left':
                return "%{$value}";
            case 'right':
                return "{$value}%";
            case 'both':
            default:
                return "%{$value}%";
        }
    }

    /**
     * 重置查询状态
     */
    protected function reset(): void
    {
        $this->data = [];
        $this->where = [];
        $this->join = [];
        $this->orderBy = [];
        $this->groupBy = [];
        $this->having = [];
        $this->field = '*';
        $this->limit = null;
        $this->offset = null;
        $this->union = null;
        $this->bindings = [];
        $this->executed = false;
    }

    /**
     * 获取最后插入ID
     */
    public function getLastInsertId(): int
    {
        return $this->lastInsertId;
    }

    /**
     * 获取影响行数
     */
    public function getAffectedRows(): int
    {
        return $this->affectedRows;
    }

    /**
     * 获取执行时间
     */
    public function getExecutionTime(): float
    {
        return $this->executionTime;
    }

    /**
     * 检查是否已执行
     */
    public function isExecuted(): bool
    {
        return $this->executed;
    }

    /**
     * 获取原始SQL（用于调试）
     */
    public function getRawSql(): string
    {
        $query = $this->buildSelect();
        return $this->interpolateQuery($query['sql'], $query['bindings']);
    }

    /**
     * 替换占位符为实际值（调试用）
     */
    protected function interpolateQuery(string $query, array $bindings): string
    {
        $tokens = array_reverse explode('\?', $query);
        $tokens = array_reverse($tokens);
        
        $result = array_shift($tokens);
        
        foreach ($bindings as $binding) {
            $token = array_shift($tokens);
            if ($token === null) break;
            
            if (is_string($binding)) {
                $result .= '"' . addslashes($binding) . '"' . $token;
            } elseif (is_null($binding)) {
                $result .= 'NULL' . $token;
            } elseif (is_bool($binding)) {
                $result .= ($binding ? '1' : '0') . $token;
            } else {
                $result .= $binding . $token;
            }
        }
        
        return $result;
    }
}
