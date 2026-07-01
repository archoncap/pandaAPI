<?php
declare(strict_types=1);

namespace PandaAPI\Database;

use PDO;
use PDOException;
use PDOStatement;
use PandaAPI\Exception\DbException;

/**
 * PandaDB - 熊猫API框架数据库类
 *
 * 完全独立实现，不依赖任何第三方 ORM（包括 Medoo），直接基于 PDO。
 * 融合 ThinkPHP 经典链式 Db 操作方式和 Medoo 风格的静态快捷方法。
 *
 * 功能概览：
 * - ThinkPHP 风格链式查询（table/name/where/join/order/limit/select/find/insert/update/delete 等）
 * - Medoo 风格静态快捷方法（select/get/insert/update/delete/replace/has/create/drop/truncate 等）
 * - 事务支持（beginTransaction/commit/rollback/transaction）
 * - 调试统计（getQueryLog/getLastSql/getQueryCount/getTotalQueryTime/getSlowQueries/setSlowThreshold）
 * - 性能优化（单例 PDO 连接、预处理语句防注入、查询构建器延迟执行）
 * - 多数据库支持（MySQL / PgSQL / SQLite）
 *
 * @package PandaAPI\Database
 */
class PandaDB
{
    // ==================== 静态属性 ====================

    /** @var array 数据库配置 */
    protected static array $config = [];

    /** @var PDO|null 单例 PDO 实例 */
    protected static ?PDO $pdo = null;

    /** @var string 当前数据库驱动类型 */
    protected static string $driver = 'mysql';

    /** @var array 查询日志 */
    protected static array $queryLog = [];

    /** @var int 查询次数统计 */
    protected static int $queryCount = 0;

    /** @var float 总查询耗时（毫秒） */
    protected static float $totalTime = 0.0;

    /** @var float 慢查询阈值（毫秒），默认 1000ms */
    protected static float $slowThreshold = 1000.0;

    /** @var array 慢查询记录 */
    protected static array $slowQueries = [];

    // ==================== 实例属性（链式查询状态） ====================

    /** @var string 当前操作的表名 */
    protected string $table = '';

    /** @var string 表别名 */
    protected string $alias = '';

    /** @var array 查询字段列表 */
    protected array $columns = ['*'];

    /** @var array WHERE 条件栈 */
    protected array $wheres = [];

    /** @var array JOIN 条件栈 */
    protected array $joins = [];

    /** @var array ORDER BY 条件栈 */
    protected array $orders = [];

    /** @var array GROUP BY 字段栈 */
    protected array $groups = [];

    /** @var string HAVING 条件 */
    protected string $having = '';

    /** @var array HAVING 绑定参数 */
    protected array $havingBindings = [];

    /** @var int LIMIT 值 */
    protected int $limit = 0;

    /** @var int OFFSET 值 */
    protected int $offset = 0;

    /** @var array 预处理绑定参数 */
    protected array $bindings = [];

    /** @var bool 是否仅返回 SQL 而不执行 */
    protected bool $fetchSql = false;

    /** @var string|null 行锁类型 */
    protected ?string $lock = null;

    /** @var bool 是否使用 distinct */
    protected bool $distinct = false;

    // ==================== 构造函数 ====================

    /**
     * 构造函数（私有，防止外部直接实例化）
     */
    private function __construct()
    {
    }

    /**
     * 克隆方法（私有，防止克隆）
     */
    private function __clone()
    {
    }

    // ==================== 一、静态配置与连接管理 ====================

    /**
     * 配置数据库连接参数
     *
     * @param array $config 配置数组，支持以下键：
     *   - driver:    数据库类型 (mysql/pgsql/sqlite)，默认 mysql
     *   - host:      主机地址，默认 127.0.0.1
     *   - port:      端口号，默认 3306
     *   - database:  数据库名
     *   - username:  用户名，默认 root
     *   - password:  密码，默认空
     *   - charset:   字符集，默认 utf8mb4
     *   - collation: 排序规则，默认 utf8mb4_unicode_ci
     *   - prefix:    表前缀，默认空
     *   - options:   PDO 选项数组
     *   - socket:    Unix socket 路径（可选）
     *   - file:      SQLite 数据库文件路径（sqlite 驱动时使用）
     * @return void
     */
    public static function config(array $config): void
    {
        self::$config = array_merge([
            'driver'    => 'mysql',
            'host'      => '127.0.0.1',
            'port'      => 3306,
            'database'  => '',
            'username'  => 'root',
            'password'  => '',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'options'   => [],
            'socket'    => '',
            'file'      => '',
        ], $config);

        // 记录驱动类型
        self::$driver = strtolower(self::$config['driver']);

        // 如果已有连接且配置变更，关闭旧连接
        self::$pdo = null;
    }

    /**
     * 获取 PDO 单例实例
     *
     * 延迟连接，首次调用时才建立连接。
     *
     * @return PDO
     * @throws DbException 连接失败时抛出异常
     */
    public static function getPdo(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = self::createConnection();
        }
        return self::$pdo;
    }

    /**
     * 创建 PDO 连接
     *
     * 根据配置的驱动类型构建 DSN 并建立连接。
     *
     * @return PDO
     * @throws DbException
     */
    protected static function createConnection(): PDO
    {
        if (empty(self::$config)) {
            throw new DbException('数据库尚未配置，请先调用 PandaDB::config() 进行配置。');
        }

        $config = self::$config;
        $driver = self::$driver;

        // 默认 PDO 选项
        $defaultOptions = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        // 合并用户自定义选项
        $options = array_merge($defaultOptions, $config['options'] ?? []);

        // 根据驱动类型构建 DSN
        switch ($driver) {
            case 'mysql':
                $dsn = self::buildMysqlDsn($config);
                break;
            case 'pgsql':
                $dsn = self::buildPgsqlDsn($config);
                break;
            case 'sqlite':
                $dsn = self::buildSqliteDsn($config);
                break;
            default:
                throw new DbException("不支持的数据库驱动类型: {$driver}");
        }

        try {
            $pdo = new PDO($dsn, $config['username'] ?? null, $config['password'] ?? null, $options);
            return $pdo;
        } catch (PDOException $e) {
            throw new DbException('数据库连接失败: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 构建 MySQL DSN 字符串
     *
     * @param array $config 配置数组
     * @return string
     */
    protected static function buildMysqlDsn(array $config): string
    {
        // 优先使用 Unix Socket
        if (!empty($config['socket'])) {
            return sprintf('mysql:unix_socket=%s;dbname=%s;charset=%s',
                $config['socket'],
                $config['database'],
                $config['charset']
            );
        }
        return sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );
    }

    /**
     * 构建 PostgreSQL DSN 字符串
     *
     * @param array $config 配置数组
     * @return string
     */
    protected static function buildPgsqlDsn(array $config): string
    {
        return sprintf('pgsql:host=%s;port=%d;dbname=%s',
            $config['host'],
            $config['port'],
            $config['database']
        );
    }

    /**
     * 构建 SQLite DSN 字符串
     *
     * @param array $config 配置数组
     * @return string
     */
    protected static function buildSqliteDsn(array $config): string
    {
        $file = $config['file'] ?? $config['database'] ?? '';
        if (empty($file)) {
            throw new DbException('SQLite 驱动需要指定 file 或 database 配置。');
        }
        return 'sqlite:' . $file;
    }

    /**
     * 关闭数据库连接
     *
     * 将 PDO 实例置空，下次操作时会重新连接。
     *
     * @return void
     */
    public static function close(): void
    {
        self::$pdo = null;
    }

    /**
     * 获取当前数据库驱动类型
     *
     * @return string
     */
    public static function getDriver(): string
    {
        return self::$driver;
    }

    /**
     * 获取表前缀
     *
     * @return string
     */
    public static function getPrefix(): string
    {
        return self::$config['prefix'] ?? '';
    }

    /**
     * 创建新的查询构建器实例（每次返回全新实例，避免状态污染）
     *
     * @return self
     */
    protected static function newQuery(): self
    {
        return new self();
    }

    // ==================== 二、ThinkPHP 风格静态入口方法 ====================

    /**
     * 设置表名，返回查询构建器实例
     *
     * 表名直接使用传入值，不自动添加前缀。
     *
     * @param string $table 完整表名
     * @return self
     *
     * 示例：
     *   PandaDB::table('users')->where('id', 1)->find();
     */
    public static function table(string $table): self
    {
        $instance = self::newQuery();
        $instance->table = $table;
        return $instance;
    }

    /**
     * 设置表名（自动添加配置中的表前缀），返回查询构建器实例
     *
     * @param string $name 不含前缀的表名
     * @return self
     *
     * 示例：
     *   PandaDB::name('user')->where('id', 1)->find();
     *   // 实际查询表名为 prefix_user
     */
    public static function name(string $name): self
    {
        $prefix = self::$config['prefix'] ?? '';
        return self::table($prefix . $name);
    }

    /**
     * 原生 SQL 查询（SELECT）
     *
     * 使用预处理语句执行查询，返回结果集数组。
     *
     * @param string $sql SQL 语句
     * @param array $bindings 绑定参数
     * @return array 查询结果集
     *
     * 示例：
     *   PandaDB::query('SELECT * FROM users WHERE id = ?', [1]);
     */
    public static function query(string $sql, array $bindings = []): array
    {
        return self::executeQuery($sql, $bindings);
    }

    /**
     * 原生 SQL 执行（INSERT/UPDATE/DELETE）
     *
     * 使用预处理语句执行写操作，返回受影响的行数。
     *
     * @param string $sql SQL 语句
     * @param array $bindings 绑定参数
     * @return int 受影响的行数
     *
     * 示例：
     *   PandaDB::execute('DELETE FROM users WHERE id = ?', [1]);
     */
    public static function execute(string $sql, array $bindings = []): int
    {
        return self::executeUpdate($sql, $bindings);
    }

    // ==================== 三、链式操作方法（实例方法） ====================

    /**
     * 设置查询字段
     *
     * 支持字符串（逗号分隔）或数组形式。
     *
     * @param string|array $fields 字段列表
     * @return $this
     *
     * 示例：
     *   ->field('id, name, email')
     *   ->field(['id', 'name', 'email'])
     *   ->field('COUNT(*) as total')
     */
    public function field($fields): self
    {
        if (is_array($fields)) {
            $this->columns = $fields;
        } elseif (is_string($fields)) {
            $this->columns = array_map('trim', explode(',', $fields));
        }
        return $this;
    }

    /**
     * 设置表别名
     *
     * @param string $alias 别名
     * @return $this
     *
     * 示例：
     *   PandaDB::table('users')->alias('u')->field('u.id, u.name')->select();
     */
    public function alias(string $alias): self
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * 设置 DISTINCT 查询
     *
     * @param bool $distinct 是否去重
     * @return $this
     */
    public function distinct(bool $distinct = true): self
    {
        $this->distinct = $distinct;
        return $this;
    }

    // ==================== WHERE 条件方法 ====================

    /**
     * 添加 WHERE 条件（AND 逻辑）
     *
     * 支持多种调用方式：
     *   - where('field', value)           → field = value
     *   - where('field', '>', value)      → field > value
     *   - where(['field1' => val1, ...])  → 批量 AND 条件
     *   - where(function($query) {...})   → 闭包子查询（嵌套条件组）
     *
     * @param mixed $field 字段名、条件数组或闭包
     * @param mixed $op 操作符或值（当省略操作符时）
     * @param mixed $value 值
     * @return $this
     */
    public function where($field, $op = null, $value = null): self
    {
        // 数组批量条件
        if (is_array($field)) {
            foreach ($field as $key => $val) {
                $this->where($key, '=', $val);
            }
            return $this;
        }

        // 闭包子查询（嵌套条件组）
        if ($field instanceof \Closure) {
            $subQuery = self::newQuery();
            $field($subQuery);
            $this->wheres[] = [
                'type'   => 'nested',
                'logic'  => 'AND',
                'query'  => $subQuery,
            ];
            return $this;
        }

        // 自动推断操作符：where('field', 'value') → where('field', '=', 'value')
        if ($value === null && $op !== null) {
            $value = $op;
            $op = '=';
        }

        $this->wheres[] = [
            'type'     => 'basic',
            'logic'    => 'AND',
            'field'    => $field,
            'operator' => strtoupper($op),
            'value'    => $value,
        ];

        return $this;
    }

    /**
     * 添加 OR WHERE 条件
     *
     * 用法与 where() 相同，逻辑连接符为 OR。
     *
     * @param mixed $field 字段名、条件数组或闭包
     * @param mixed $op 操作符或值
     * @param mixed $value 值
     * @return $this
     */
    public function whereOr($field, $op = null, $value = null): self
    {
        // 数组批量条件
        if (is_array($field)) {
            foreach ($field as $key => $val) {
                $this->whereOr($key, '=', $val);
            }
            return $this;
        }

        // 闭包子查询
        if ($field instanceof \Closure) {
            $subQuery = self::newQuery();
            $field($subQuery);
            $this->wheres[] = [
                'type'   => 'nested',
                'logic'  => 'OR',
                'query'  => $subQuery,
            ];
            return $this;
        }

        // 自动推断操作符
        if ($value === null && $op !== null) {
            $value = $op;
            $op = '=';
        }

        $this->wheres[] = [
            'type'     => 'basic',
            'logic'    => 'OR',
            'field'    => $field,
            'operator' => strtoupper($op),
            'value'    => $value,
        ];

        return $this;
    }

    /**
     * WHERE IN 条件
     *
     * @param string $field 字段名
     * @param array $values 值数组
     * @return $this
     *
     * 示例：
     *   ->whereIn('id', [1, 2, 3])
     */
    public function whereIn(string $field, array $values): self
    {
        if (empty($values)) {
            // 空数组时使用不可能满足的条件
            $this->wheres[] = [
                'type'     => 'raw',
                'logic'    => 'AND',
                'sql'      => '1 = 0',
                'bindings' => [],
            ];
            return $this;
        }

        $this->wheres[] = [
            'type'     => 'in',
            'logic'    => 'AND',
            'field'    => $field,
            'operator' => 'IN',
            'values'   => $values,
        ];
        return $this;
    }

    /**
     * WHERE NOT IN 条件
     *
     * @param string $field 字段名
     * @param array $values 值数组
     * @return $this
     */
    public function whereNotIn(string $field, array $values): self
    {
        if (empty($values)) {
            // 空数组 NOT IN 等价于无限制条件
            return $this;
        }

        $this->wheres[] = [
            'type'     => 'in',
            'logic'    => 'AND',
            'field'    => $field,
            'operator' => 'NOT IN',
            'values'   => $values,
        ];
        return $this;
    }

    /**
     * WHERE BETWEEN 条件
     *
     * @param string $field 字段名
     * @param mixed $min 最小值
     * @param mixed $max 最大值
     * @return $this
     *
     * 示例：
     *   ->whereBetween('age', 18, 60)
     */
    public function whereBetween(string $field, $min, $max): self
    {
        $this->wheres[] = [
            'type'     => 'between',
            'logic'    => 'AND',
            'field'    => $field,
            'operator' => 'BETWEEN',
            'min'      => $min,
            'max'      => $max,
        ];
        return $this;
    }

    /**
     * WHERE NOT BETWEEN 条件
     *
     * @param string $field 字段名
     * @param mixed $min 最小值
     * @param mixed $max 最大值
     * @return $this
     */
    public function whereNotBetween(string $field, $min, $max): self
    {
        $this->wheres[] = [
            'type'     => 'between',
            'logic'    => 'AND',
            'field'    => $field,
            'operator' => 'NOT BETWEEN',
            'min'      => $min,
            'max'      => $max,
        ];
        return $this;
    }

    /**
     * WHERE LIKE 模糊查询
     *
     * @param string $field 字段名
     * @param string $value 匹配值（不含通配符，方法自动添加）
     * @param string $type 匹配方式：both（两端）、left（左端）、right（右端）
     * @return $this
     *
     * 示例：
     *   ->whereLike('name', '张', 'both')   → %张%
     *   ->whereLike('name', '张', 'left')   → %张
     *   ->whereLike('name', '张', 'right')  → 张%
     */
    public function whereLike(string $field, string $value, string $type = 'both'): self
    {
        switch ($type) {
            case 'left':
                $likeValue = '%' . $value;
                break;
            case 'right':
                $likeValue = $value . '%';
                break;
            case 'both':
            default:
                $likeValue = '%' . $value . '%';
                break;
        }

        $this->wheres[] = [
            'type'     => 'basic',
            'logic'    => 'AND',
            'field'    => $field,
            'operator' => 'LIKE',
            'value'    => $likeValue,
        ];
        return $this;
    }

    /**
     * WHERE IS NULL 条件
     *
     * @param string $field 字段名
     * @return $this
     */
    public function whereNull(string $field): self
    {
        $this->wheres[] = [
            'type'     => 'null',
            'logic'    => 'AND',
            'field'    => $field,
            'operator' => 'IS NULL',
        ];
        return $this;
    }

    /**
     * WHERE IS NOT NULL 条件
     *
     * @param string $field 字段名
     * @return $this
     */
    public function whereNotNull(string $field): self
    {
        $this->wheres[] = [
            'type'     => 'null',
            'logic'    => 'AND',
            'field'    => $field,
            'operator' => 'IS NOT NULL',
        ];
        return $this;
    }

    /**
     * WHERE EXISTS 子查询
     *
     * @param string|\Closure $sql 子查询 SQL 或闭包
     * @param array $bindings 绑定参数（仅 sql 为字符串时有效）
     * @return $this
     *
     * 示例：
     *   ->whereExists('SELECT 1 FROM orders WHERE orders.user_id = users.id')
     *   ->whereExists(function($query) {
     *       $query->table('orders')->whereRaw('orders.user_id = users.id');
     *   })
     */
    public function whereExists($sql, array $bindings = []): self
    {
        if ($sql instanceof \Closure) {
            $subQuery = self::newQuery();
            $sql($subQuery);
            $this->wheres[] = [
                'type'     => 'exists',
                'logic'    => 'AND',
                'operator' => 'EXISTS',
                'query'    => $subQuery,
            ];
        } else {
            $this->wheres[] = [
                'type'     => 'raw',
                'logic'    => 'AND',
                'sql'      => 'EXISTS (' . $sql . ')',
                'bindings' => $bindings,
            ];
        }
        return $this;
    }

    /**
     * WHERE NOT EXISTS 子查询
     *
     * @param string|\Closure $sql 子查询 SQL 或闭包
     * @param array $bindings 绑定参数
     * @return $this
     */
    public function whereNotExists($sql, array $bindings = []): self
    {
        if ($sql instanceof \Closure) {
            $subQuery = self::newQuery();
            $sql($subQuery);
            $this->wheres[] = [
                'type'     => 'exists',
                'logic'    => 'AND',
                'operator' => 'NOT EXISTS',
                'query'    => $subQuery,
            ];
        } else {
            $this->wheres[] = [
                'type'     => 'raw',
                'logic'    => 'AND',
                'sql'      => 'NOT EXISTS (' . $sql . ')',
                'bindings' => $bindings,
            ];
        }
        return $this;
    }

    /**
     * 原始 WHERE 条件（直接拼接 SQL 片段）
     *
     * @param string $sql SQL 片段
     * @param array $bindings 绑定参数
     * @return $this
     *
     * 示例：
     *   ->whereRaw('YEAR(created_at) = ?', [2024])
     */
    public function whereRaw(string $sql, array $bindings = []): self
    {
        $this->wheres[] = [
            'type'     => 'raw',
            'logic'    => 'AND',
            'sql'      => $sql,
            'bindings' => $bindings,
        ];
        return $this;
    }

    // ==================== JOIN 方法 ====================

    /**
     * 添加 JOIN 子句
     *
     * @param string $table 关联表名
     * @param string|\Closure $on 关联条件（字符串或闭包）
     * @param string $type JOIN 类型（INNER/LEFT/RIGHT/CROSS）
     * @return $this
     *
     * 示例：
     *   ->join('orders', 'users.id = orders.user_id')
     *   ->join('orders', 'users.id = orders.user_id', 'LEFT')
     *   ->join('orders', function($join) {
     *       $join->on('users.id', 'orders.user_id')
     *            ->where('orders.status', 'paid');
     *   }, 'LEFT')
     */
    public function join(string $table, $on, string $type = 'INNER'): self
    {
        $type = strtoupper($type);

        if ($on instanceof \Closure) {
            // 闭包形式，构建复杂 JOIN 条件
            $joinBuilder = new JoinClause($table, $type);
            $on($joinBuilder);
            $this->joins[] = $joinBuilder;
        } else {
            // 字符串形式
            $this->joins[] = [
                'type'  => $type,
                'table' => $table,
                'on'    => $on,
                'bindings' => [],
            ];
        }

        return $this;
    }

    /**
     * LEFT JOIN 关联
     *
     * @param string $table 关联表名
     * @param string|\Closure $on 关联条件
     * @return $this
     */
    public function leftJoin(string $table, $on): self
    {
        return $this->join($table, $on, 'LEFT');
    }

    /**
     * RIGHT JOIN 关联
     *
     * @param string $table 关联表名
     * @param string|\Closure $on 关联条件
     * @return $this
     */
    public function rightJoin(string $table, $on): self
    {
        return $this->join($table, $on, 'RIGHT');
    }

    /**
     * CROSS JOIN 关联
     *
     * @param string $table 关联表名
     * @param string|\Closure $on 关联条件（可选）
     * @return $this
     */
    public function crossJoin(string $table, $on = null): self
    {
        return $this->join($table, $on ?? '1=1', 'CROSS');
    }

    // ==================== ORDER / GROUP / HAVING 方法 ====================

    /**
     * 添加 ORDER BY 排序
     *
     * @param string|array $field 字段名或字段=>方向数组
     * @param string $direction 排序方向（ASC/DESC）
     * @return $this
     *
     * 示例：
     *   ->order('id', 'DESC')
     *   ->order(['id' => 'DESC', 'name' => 'ASC'])
     *   ->order('created_at DESC, id ASC')
     */
    public function order($field, string $direction = 'ASC'): self
    {
        if (is_array($field)) {
            foreach ($field as $key => $dir) {
                if (is_int($key)) {
                    // 纯字符串排序表达式
                    $this->orders[] = ['expr' => $dir, 'bindings' => []];
                } else {
                    $this->orders[] = ['expr' => $key . ' ' . strtoupper($dir), 'bindings' => []];
                }
            }
        } else {
            $this->orders[] = ['expr' => $field . ' ' . strtoupper($direction), 'bindings' => []];
        }
        return $this;
    }

    /**
     * 添加原始 ORDER BY 表达式
     *
     * @param string $sql 排序表达式
     * @param array $bindings 绑定参数
     * @return $this
     *
     * 示例：
     *   ->orderRaw('FIELD(status, "active", "pending", "deleted")')
     */
    public function orderRaw(string $sql, array $bindings = []): self
    {
        $this->orders[] = ['expr' => $sql, 'bindings' => $bindings];
        return $this;
    }

    /**
     * 添加 GROUP BY 分组
     *
     * @param string|array $field 字段名或字段数组
     * @return $this
     *
     * 示例：
     *   ->group('category_id')
     *   ->group(['category_id', 'status'])
     */
    public function group($field): self
    {
        if (is_array($field)) {
            foreach ($field as $f) {
                $this->groups[] = $f;
            }
        } else {
            $this->groups[] = $field;
        }
        return $this;
    }

    /**
     * 添加原始 GROUP BY 表达式
     *
     * @param string $sql 分组表达式
     * @param array $bindings 绑定参数
     * @return $this
     *
     * 示例：
     *   ->groupRaw('DATE(created_at)')
     */
    public function groupRaw(string $sql, array $bindings = []): self
    {
        $this->groups[] = $sql;
        // 将绑定参数暂存到 groups 相关位置
        if (!empty($bindings)) {
            $this->groups['_bindings_' . count($this->groups)] = $bindings;
        }
        return $this;
    }

    /**
     * 添加 HAVING 条件
     *
     * @param string $condition HAVING 条件表达式
     * @param array $bindings 绑定参数
     * @return $this
     *
     * 示例：
     *   ->having('COUNT(*) > ?', [10])
     */
    public function having(string $condition, array $bindings = []): self
    {
        $this->having = $condition;
        $this->havingBindings = $bindings;
        return $this;
    }

    // ==================== LIMIT / OFFSET / PAGE 方法 ====================

    /**
     * 设置 LIMIT 限制
     *
     * @param int $limit 限制数量
     * @param int|null $offset 偏移量（可选）
     * @return $this
     *
     * 示例：
     *   ->limit(10)
     *   ->limit(10, 20)  // 跳过前20条，取10条
     */
    public function limit(int $limit, ?int $offset = null): self
    {
        $this->limit = $limit;
        if ($offset !== null) {
            $this->offset = $offset;
        }
        return $this;
    }

    /**
     * 设置 OFFSET 偏移量
     *
     * @param int $offset 偏移量
     * @return $this
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * 设置分页参数
     *
     * @param int $page 当前页码（从1开始）
     * @param int $perPage 每页数量
     * @return $this
     *
     * 示例：
     *   ->page(2, 15)  // 第2页，每页15条
     */
    public function page(int $page, int $perPage = 15): self
    {
        $this->limit = $perPage;
        $this->offset = max(0, ($page - 1) * $perPage);
        return $this;
    }

    // ==================== LOCK 锁方法 ====================

    /**
     * 设置行锁
     *
     * @param bool|string $value true 或 'FOR UPDATE' 表示排他锁，false 或 'SHARE' 表示共享锁
     * @return $this
     *
     * 示例：
     *   ->lock(true)              // SELECT ... FOR UPDATE
     *   ->lock('FOR UPDATE')      // SELECT ... FOR UPDATE
     *   ->lock('LOCK IN SHARE MODE')  // SELECT ... LOCK IN SHARE MODE
     *   ->lock(false)             // 不加锁
     */
    public function lock($value = true): self
    {
        if ($value === false) {
            $this->lock = null;
        } elseif ($value === true || $value === 'FOR UPDATE') {
            $this->lock = 'FOR UPDATE';
        } elseif ($value === 'SHARE' || $value === 'LOCK IN SHARE MODE') {
            $this->lock = 'LOCK IN SHARE MODE';
        } else {
            $this->lock = (string) $value;
        }
        return $this;
    }

    // ==================== FETCH SQL 方法 ====================

    /**
     * 设置是否仅返回 SQL 而不执行
     *
     * @param bool $bool 是否仅返回 SQL
     * @return $this
     *
     * 示例：
     *   ->fetchSql()->select()  // 返回 ['sql' => '...', 'bindings' => [...]]
     */
    public function fetchSql(bool $bool = true): self
    {
        $this->fetchSql = $bool;
        return $this;
    }

    // ==================== 四、查询执行方法 ====================

    /**
     * 查询多条记录
     *
     * @return array 结果集数组；fetchSql 模式下返回 ['sql' => ..., 'bindings' => ...]
     */
    public function select(): array
    {
        $sql = $this->buildSelectSql();

        if ($this->fetchSql) {
            $result = ['sql' => $sql, 'bindings' => $this->bindings];
            $this->resetQuery();
            return $result;
        }

        $result = self::executeQuery($sql, $this->bindings);
        $this->resetQuery();
        return $result;
    }

    /**
     * 查询单条记录
     *
     * @return array|null 单条记录或 null
     */
    public function find(): ?array
    {
        $this->limit = 1;
        $results = $this->select();

        if ($this->fetchSql) {
            // fetchSql 模式下 select 已返回 sql 数组
            return $results;
        }

        return $results[0] ?? null;
    }

    /**
     * 获取单个字段的值
     *
     * @param string $field 字段名
     * @return mixed 字段值，无结果时返回 null
     *
     * 示例：
     *   PandaDB::table('users')->where('id', 1)->value('name');
     */
    public function value(string $field)
    {
        $this->field($field);
        $row = $this->find();

        if ($this->fetchSql) {
            return $row;
        }

        if (!$row) {
            return null;
        }
        return array_values($row)[0] ?? null;
    }

    /**
     * 获取某列的所有值（一维数组）
     *
     * @param string $field 字段名
     * @return array 值数组
     *
     * 示例：
     *   PandaDB::table('users')->where('status', 'active')->column('email');
     */
    public function column(string $field): array
    {
        $this->field($field);
        $results = $this->select();

        if ($this->fetchSql) {
            return $results;
        }

        return array_map('reset', $results);
    }

    /**
     * 插入单条数据
     *
     * @param array $data 键值对数组
     * @return int|string 最后插入的 ID；fetchSql 模式下返回数组
     *
     * 示例：
     *   PandaDB::table('users')->insert(['name' => '张三', 'email' => 'zhangsan@example.com']);
     */
    public function insert(array $data)
    {
        $sql = $this->buildInsertSql($data);

        if ($this->fetchSql) {
            $result = ['sql' => $sql, 'bindings' => $this->bindings];
            $this->resetQuery();
            return $result;
        }

        self::executeUpdate($sql, $this->bindings);
        $lastId = self::getPdo()->lastInsertId();
        $this->resetQuery();
        return $lastId ? (int) $lastId : 0;
    }

    /**
     * 批量插入数据
     *
     * @param array $dataSet 二维数组，每个元素为一行数据
     * @return int 受影响的行数；fetchSql 模式下返回数组
     *
     * 示例：
     *   PandaDB::table('users')->insertAll([
     *       ['name' => '张三', 'email' => 'zhangsan@example.com'],
     *       ['name' => '李四', 'email' => 'lisi@example.com'],
     *   ]);
     */
    public function insertAll(array $dataSet)
    {
        if (empty($dataSet)) {
            return 0;
        }

        $sql = $this->buildBatchInsertSql($dataSet);

        if ($this->fetchSql) {
            $result = ['sql' => $sql, 'bindings' => $this->bindings];
            $this->resetQuery();
            return $result;
        }

        $result = self::executeUpdate($sql, $this->bindings);
        $this->resetQuery();
        return $result;
    }

    /**
     * 插入数据并返回自增 ID（insert 的别名）
     *
     * @param array $data 键值对数组
     * @return int 最后插入的 ID
     */
    public function insertGetId(array $data): int
    {
        return (int) $this->insert($data);
    }

    /**
     * 更新数据
     *
     * @param array $data 键值对数组
     * @return int 受影响的行数；fetchSql 模式下返回数组
     *
     * 示例：
     *   PandaDB::table('users')->where('id', 1)->update(['name' => '新名字']);
     */
    public function update(array $data)
    {
        if (empty($this->wheres)) {
            throw new DbException('更新操作必须指定 WHERE 条件，防止全表更新。');
        }

        $sql = $this->buildUpdateSql($data);

        if ($this->fetchSql) {
            $result = ['sql' => $sql, 'bindings' => $this->bindings];
            $this->resetQuery();
            return $result;
        }

        $result = self::executeUpdate($sql, $this->bindings);
        $this->resetQuery();
        return $result;
    }

    /**
     * 删除数据
     *
     * @return int 受影响的行数；fetchSql 模式下返回数组
     *
     * 示例：
     *   PandaDB::table('users')->where('id', 1)->delete();
     */
    public function delete()
    {
        if (empty($this->wheres)) {
            throw new DbException('删除操作必须指定 WHERE 条件，防止全表删除。');
        }

        $sql = $this->buildDeleteSql();

        if ($this->fetchSql) {
            $result = ['sql' => $sql, 'bindings' => $this->bindings];
            $this->resetQuery();
            return $result;
        }

        $result = self::executeUpdate($sql, $this->bindings);
        $this->resetQuery();
        return $result;
    }

    /**
     * 原子递增某个字段的值
     *
     * @param string $field 字段名
     * @param int|float $step 步长，默认 1
     * @return int 受影响的行数
     *
     * 示例：
     *   PandaDB::table('users')->where('id', 1)->increment('login_count');
     *   PandaDB::table('products')->where('id', 5)->increment('stock', -1);
     */
    public function increment(string $field, $step = 1): int
    {
        $expression = ($step >= 0 ? '' : '') . $this->wrapField($field) . ' = ' . $this->wrapField($field) . ' + ?';
        $sql = "UPDATE " . $this->wrapTable() . " SET {$expression}";

        $whereSql = $this->buildWhereSql();
        $sql .= $whereSql;

        $this->bindings = array_merge([$step], $this->bindings);

        if ($this->fetchSql) {
            $result = ['sql' => $sql, 'bindings' => $this->bindings];
            $this->resetQuery();
            return $result;
        }

        $result = self::executeUpdate($sql, $this->bindings);
        $this->resetQuery();
        return $result;
    }

    /**
     * 原子递减某个字段的值
     *
     * @param string $field 字段名
     * @param int|float $step 步长，默认 1
     * @return int 受影响的行数
     *
     * 示例：
     *   PandaDB::table('products')->where('id', 5)->decrement('stock');
     */
    public function decrement(string $field, $step = 1): int
    {
        return $this->increment($field, -$step);
    }

    // ==================== 五、聚合查询方法 ====================

    /**
     * 统计记录数
     *
     * @param string $field 字段名，默认 *
     * @return int
     */
    public function count(string $field = '*'): int
    {
        return (int) $this->aggregate('COUNT', $field);
    }

    /**
     * 求和
     *
     * @param string $field 字段名
     * @return float
     */
    public function sum(string $field): float
    {
        return (float) $this->aggregate('SUM', $field);
    }

    /**
     * 求平均值
     *
     * @param string $field 字段名
     * @return float
     */
    public function avg(string $field): float
    {
        return (float) $this->aggregate('AVG', $field);
    }

    /**
     * 求最大值
     *
     * @param string $field 字段名
     * @return float
     */
    public function max(string $field): float
    {
        return (float) $this->aggregate('MAX', $field);
    }

    /**
     * 求最小值
     *
     * @param string $field 字段名
     * @return float
     */
    public function min(string $field): float
    {
        return (float) $this->aggregate('MIN', $field);
    }

    /**
     * 执行聚合查询
     *
     * @param string $function 聚合函数名（COUNT/SUM/AVG/MAX/MIN）
     * @param string $field 字段名
     * @return mixed 聚合结果
     */
    protected function aggregate(string $function, string $field)
    {
        $this->field("{$function}({$field}) AS __aggregate__");
        $row = $this->find();

        if ($this->fetchSql) {
            return $row;
        }

        $this->resetQuery();
        return $row['__aggregate__'] ?? 0;
    }

    // ==================== 六、分页方法 ====================

    /**
     * 分页查询
     *
     * 自动计算总数、总页数，返回分页数据。
     *
     * @param int $perPage 每页数量，默认 15
     * @param int|null $currentPage 当前页码，默认从 $_GET['page'] 获取
     * @param array $simpleColumns 简单模式下的字段列表（可选）
     * @return array 分页结果，包含 data/total/per_page/current_page/last_page/total_pages
     *
     * 示例：
     *   $result = PandaDB::table('users')->where('status', 'active')->paginate(15, 2);
     *   // $result = ['data' => [...], 'total' => 100, 'per_page' => 15, 'current_page' => 2, 'last_page' => 7]
     */
    public function paginate(int $perPage = 15, ?int $currentPage = null): array
    {
        $currentPage = $currentPage ?? (int)($_GET['page'] ?? 1);
        $currentPage = max(1, $currentPage);

        // 先查询总数（克隆当前查询状态）
        $countQuery = clone $this;
        $countQuery->columns = ['COUNT(*) AS __total__'];
        $countQuery->orders = [];
        $countQuery->limit = 0;
        $countQuery->offset = 0;
        $countQuery->lock = null;

        $countSql = $countQuery->buildSelectSql();
        $countResult = self::executeQuery($countSql, $countQuery->bindings);
        $total = (int) ($countResult[0]['__total__'] ?? 0);

        $lastPage = (int) ceil($total / $perPage);
        if ($lastPage < 1) {
            $lastPage = 1;
        }

        // 设置分页参数并查询数据
        $this->page($currentPage, $perPage);
        $data = $this->select();

        return [
            'data'         => $data,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $currentPage,
            'last_page'    => $lastPage,
            'total_pages'  => $lastPage,
        ];
    }

    /**
     * 获取当前构建器生成的 SQL（用于调试）
     *
     * @return string SQL 语句
     */
    public function getSql(): string
    {
        return $this->buildSelectSql();
    }

    /**
     * 获取当前构建器的绑定参数
     *
     * @return array
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    // ==================== 七、SQL 构建方法 ====================

    /**
     * 构建 SELECT SQL 语句
     *
     * @return string
     */
    protected function buildSelectSql(): string
    {
        // DISTINCT 关键字
        $distinct = $this->distinct ? 'DISTINCT ' : '';

        // 字段列表
        $columns = implode(', ', $this->columns);

        // 表名（含别名）
        $table = $this->wrapTable();

        $sql = "SELECT {$distinct}{$columns} FROM {$table}";

        // JOIN
        $sql .= $this->buildJoinSql();

        // WHERE
        $sql .= $this->buildWhereSql();

        // GROUP BY
        $sql .= $this->buildGroupSql();

        // HAVING
        $sql .= $this->buildHavingSql();

        // ORDER BY
        $sql .= $this->buildOrderSql();

        // LIMIT / OFFSET
        $sql .= $this->buildLimitSql();

        // LOCK
        if ($this->lock !== null) {
            $sql .= ' ' . $this->lock;
        }

        return $sql;
    }

    /**
     * 构建 INSERT SQL 语句
     *
     * @param array $data 键值对数据
     * @return string
     */
    protected function buildInsertSql(array $data): string
    {
        $columns = array_keys($data);
        $values = array_values($data);

        $this->bindings = $values;

        $columnStr = implode(', ', array_map([$this, 'wrapField'], $columns));
        $placeholders = implode(', ', array_fill(0, count($values), '?'));

        return "INSERT INTO " . $this->wrapTable() . " ({$columnStr}) VALUES ({$placeholders})";
    }

    /**
     * 构建批量 INSERT SQL 语句
     *
     * @param array $dataSet 二维数组
     * @return string
     */
    protected function buildBatchInsertSql(array $dataSet): string
    {
        $columns = array_keys($dataSet[0]);
        $columnStr = implode(', ', array_map([$this, 'wrapField'], $columns));

        $valueStrings = [];
        $this->bindings = [];

        foreach ($dataSet as $data) {
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));
            $valueStrings[] = "({$placeholders})";
            $this->bindings = array_merge($this->bindings, array_values($data));
        }

        return "INSERT INTO " . $this->wrapTable() . " ({$columnStr}) VALUES " . implode(', ', $valueStrings);
    }

    /**
     * 构建 UPDATE SQL 语句
     *
     * @param array $data 键值对数据
     * @return string
     */
    protected function buildUpdateSql(array $data): string
    {
        $sets = [];
        $values = [];

        foreach ($data as $column => $value) {
            $sets[] = $this->wrapField($column) . ' = ?';
            $values[] = $value;
        }

        // 先收集 WHERE 绑定参数（buildWhereSql 会追加到 $this->bindings）
        $whereBindings = [];
        $tempBindings = $this->bindings;
        $this->bindings = [];

        $whereSql = $this->buildWhereSql();
        $whereBindings = $this->bindings;
        $this->bindings = $tempBindings;

        // SET 参数在前，WHERE 参数在后
        $this->bindings = array_merge($values, $whereBindings);

        $setStr = implode(', ', $sets);
        return "UPDATE " . $this->wrapTable() . " SET {$setStr}{$whereSql}";
    }

    /**
     * 构建 DELETE SQL 语句
     *
     * @return string
     */
    protected function buildDeleteSql(): string
    {
        $whereSql = $this->buildWhereSql();
        return "DELETE FROM " . $this->wrapTable() . "{$whereSql}";
    }

    /**
     * 构建 JOIN SQL 片段
     *
     * @return string
     */
    protected function buildJoinSql(): string
    {
        if (empty($this->joins)) {
            return '';
        }

        $sql = '';
        foreach ($this->joins as $join) {
            if ($join instanceof JoinClause) {
                $sql .= ' ' . $join->toSql($this);
            } else {
                $type = $join['type'];
                $table = $this->wrapField($join['table']);
                $on = $join['on'];
                $sql .= " {$type} JOIN {$table} ON {$on}";

                if (!empty($join['bindings'])) {
                    $this->bindings = array_merge($this->bindings, $join['bindings']);
                }
            }
        }
        return $sql;
    }

    /**
     * 构建 WHERE SQL 片段
     *
     * 遍历 wheres 栈，根据类型分别构建条件，并收集绑定参数。
     *
     * @return string
     */
    protected function buildWhereSql(): string
    {
        if (empty($this->wheres)) {
            return '';
        }

        $sql = ' WHERE ';
        $parts = [];

        foreach ($this->wheres as $index => $where) {
            $condition = $this->buildWhereCondition($where, $index);
            if ($index === 0) {
                $parts[] = $condition;
            } else {
                $logic = $where['logic'] ?? 'AND';
                $parts[] = "{$logic} {$condition}";
            }
        }

        return $sql . implode(' ', $parts);
    }

    /**
     * 构建单个 WHERE 条件
     *
     * @param array $where 条件定义
     * @param int $index 条件索引
     * @return string SQL 片段
     */
    protected function buildWhereCondition(array $where, int $index = 0): string
    {
        $type = $where['type'] ?? 'basic';

        switch ($type) {
            case 'basic':
                // 基本条件：field operator value
                $field = $this->wrapField($where['field']);
                $operator = $where['operator'];
                $this->bindings[] = $where['value'];
                return "{$field} {$operator} ?";

            case 'in':
                // IN / NOT IN 条件
                $field = $this->wrapField($where['field']);
                $operator = $where['operator'];
                $values = $where['values'];
                $placeholders = implode(', ', array_fill(0, count($values), '?'));
                $this->bindings = array_merge($this->bindings, $values);
                return "{$field} {$operator} ({$placeholders})";

            case 'between':
                // BETWEEN / NOT BETWEEN 条件
                $field = $this->wrapField($where['field']);
                $operator = $where['operator'];
                $this->bindings[] = $where['min'];
                $this->bindings[] = $where['max'];
                return "{$field} {$operator} ? AND ?";

            case 'null':
                // IS NULL / IS NOT NULL 条件
                $field = $this->wrapField($where['field']);
                return "{$field} {$where['operator']}";

            case 'raw':
                // 原始 SQL 条件
                if (!empty($where['bindings'])) {
                    $this->bindings = array_merge($this->bindings, $where['bindings']);
                }
                return $where['sql'];

            case 'nested':
                // 嵌套闭包条件组
                $subQuery = $where['query'];
                $nestedSql = $subQuery->buildWhereSql();
                // 去掉开头的 " WHERE "，用括号包裹
                $nestedSql = preg_replace('/^\\s*WHERE\\s*/i', '', $nestedSql);
                // 收集子查询的绑定参数
                $this->bindings = array_merge($this->bindings, $subQuery->bindings);
                return "({$nestedSql})";

            case 'exists':
                // EXISTS / NOT EXISTS 子查询
                $subQuery = $where['query'];
                $subSql = $subQuery->buildSelectSql();
                $this->bindings = array_merge($this->bindings, $subQuery->bindings);
                return "{$where['operator']} ({$subSql})";

            default:
                return '';
        }
    }

    /**
     * 构建 GROUP BY SQL 片段
     *
     * @return string
     */
    protected function buildGroupSql(): string
    {
        if (empty($this->groups)) {
            return '';
        }

        $groupParts = [];
        foreach ($this->groups as $key => $group) {
            if (is_int($key)) {
                $groupParts[] = $group;
            }
        }

        return ' GROUP BY ' . implode(', ', $groupParts);
    }

    /**
     * 构建 HAVING SQL 片段
     *
     * @return string
     */
    protected function buildHavingSql(): string
    {
        if (empty($this->having)) {
            return '';
        }

        if (!empty($this->havingBindings)) {
            $this->bindings = array_merge($this->bindings, $this->havingBindings);
        }

        return ' HAVING ' . $this->having;
    }

    /**
     * 构建 ORDER BY SQL 片段
     *
     * @return string
     */
    protected function buildOrderSql(): string
    {
        if (empty($this->orders)) {
            return '';
        }

        $orderParts = [];
        foreach ($this->orders as $order) {
            $orderParts[] = $order['expr'];
            if (!empty($order['bindings'])) {
                $this->bindings = array_merge($this->bindings, $order['bindings']);
            }
        }

        return ' ORDER BY ' . implode(', ', $orderParts);
    }

    /**
     * 构建 LIMIT / OFFSET SQL 片段
     *
     * @return string
     */
    protected function buildLimitSql(): string
    {
        if ($this->limit <= 0) {
            return '';
        }

        $driver = self::$driver;

        if ($driver === 'pgsql') {
            // PostgreSQL 使用 LIMIT x OFFSET y
            $sql = " LIMIT {$this->limit}";
            if ($this->offset > 0) {
                $sql .= " OFFSET {$this->offset}";
            }
            return $sql;
        }

        // MySQL / SQLite 使用 LIMIT offset, count 或 LIMIT count OFFSET offset
        if ($this->offset > 0) {
            return " LIMIT {$this->offset}, {$this->limit}";
        }
        return " LIMIT {$this->limit}";
    }

    // ==================== 八、字段/表名包装方法 ====================

    /**
     * 包装表名（添加反引号或双引号）
     *
     * 根据数据库驱动类型选择合适的引用符号。
     *
     * @return string
     */
    protected function wrapTable(): string
    {
        $table = $this->table;
        if (!empty($this->alias)) {
            $table .= ' AS ' . $this->wrapIdentifier($this->alias);
        }
        return $this->wrapIdentifier($table);
    }

    /**
     * 包装字段名
     *
     * @param string $field 字段名
     * @return string
     */
    protected function wrapField(string $field): string
    {
        // 如果字段已包含 AS 或空格，说明有别名，分别包装
        if (stripos($field, ' AS ') !== false || stripos($field, ' as ') !== false) {
            $parts = preg_split('/\s+[Aa][Ss]\s+/', $field);
            return $this->wrapIdentifier(trim($parts[0])) . ' AS ' . $this->wrapIdentifier(trim($parts[1] ?? ''));
        }

        // 如果字段包含函数或特殊字符（如 COUNT(*), YEAR(...)），不包装
        if (preg_match('/[(*]/', $field)) {
            return $field;
        }

        // 如果字段包含点号（如 table.field），分别包装
        if (strpos($field, '.') !== false) {
            $segments = explode('.', $field);
            return $this->wrapIdentifier($segments[0]) . '.' . $this->wrapIdentifier($segments[1]);
        }

        return $this->wrapIdentifier($field);
    }

    /**
     * 使用数据库对应的标识符引用符号包装名称
     *
     * MySQL/SQLite 使用反引号 `，PostgreSQL 使用双引号 "。
     *
     * @param string $identifier 标识符
     * @return string
     */
    protected function wrapIdentifier(string $identifier): string
    {
        if (self::$driver === 'pgsql') {
            return '"' . str_replace('"', '""', $identifier) . '"';
        }
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    // ==================== 九、重置查询状态 ====================

    /**
     * 重置查询构建器状态
     *
     * 每次执行完查询后调用，确保下次查询不受上次条件影响。
     *
     * @return void
     */
    protected function resetQuery(): void
    {
        $this->columns = ['*'];
        $this->alias = '';
        $this->wheres = [];
        $this->joins = [];
        $this->orders = [];
        $this->groups = [];
        $this->having = '';
        $this->havingBindings = [];
        $this->limit = 0;
        $this->offset = 0;
        $this->bindings = [];
        $this->fetchSql = false;
        $this->lock = null;
        $this->distinct = false;
    }

    // ==================== 十、底层执行方法 ====================

    /**
     * 执行查询 SQL（SELECT）
     *
     * 使用 PDO 预处理语句，记录查询日志。
     *
     * @param string $sql SQL 语句
     * @param array $bindings 绑定参数
     * @return array 结果集
     * @throws DbException
     */
    protected static function executeQuery(string $sql, array $bindings = []): array
    {
        $start = microtime(true);

        try {
            $stmt = self::getPdo()->prepare($sql);
            $stmt->execute($bindings);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            self::logQuery($sql, $bindings, (microtime(true) - $start) * 1000);
            throw new DbException('查询执行失败: ' . $e->getMessage() . ' | SQL: ' . $sql, 0, $e);
        }

        $time = (microtime(true) - $start) * 1000;
        self::logQuery($sql, $bindings, $time);

        return $result;
    }

    /**
     * 执行写操作 SQL（INSERT/UPDATE/DELETE）
     *
     * 使用 PDO 预处理语句，记录查询日志，返回受影响行数。
     *
     * @param string $sql SQL 语句
     * @param array $bindings 绑定参数
     * @return int 受影响的行数
     * @throws DbException
     */
    protected static function executeUpdate(string $sql, array $bindings = []): int
    {
        $start = microtime(true);

        try {
            $stmt = self::getPdo()->prepare($sql);
            $stmt->execute($bindings);
            $rowCount = $stmt->rowCount();
        } catch (PDOException $e) {
            self::logQuery($sql, $bindings, (microtime(true) - $start) * 1000);
            throw new DbException('写操作执行失败: ' . $e->getMessage() . ' | SQL: ' . $sql, 0, $e);
        }

        $time = (microtime(true) - $start) * 1000;
        self::logQuery($sql, $bindings, $time);

        return $rowCount;
    }

    // ==================== 十一、事务支持 ====================

    /**
     * 开启事务
     *
     * @return bool
     * @throws DbException
     */
    public static function beginTransaction(): bool
    {
        try {
            return self::getPdo()->beginTransaction();
        } catch (PDOException $e) {
            throw new DbException('开启事务失败: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 提交事务
     *
     * @return bool
     * @throws DbException
     */
    public static function commit(): bool
    {
        try {
            return self::getPdo()->commit();
        } catch (PDOException $e) {
            throw new DbException('提交事务失败: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 回滚事务
     *
     * @return bool
     * @throws DbException
     */
    public static function doRollback(): bool
    {
        try {
            return self::getPdo()->rollBack();
        } catch (PDOException $e) {
            throw new DbException('回滚事务失败: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 在事务中执行回调
     *
     * 自动开启事务、提交或回滚。如果回调抛出异常，自动回滚并重新抛出。
     *
     * @param callable $callback 回调函数，接收 PandaDB 实例作为参数
     * @return mixed 回调函数的返回值
     * @throws \Exception
     * @throws DbException
     *
     * 示例：
     *   PandaDB::transaction(function() {
     *       PandaDB::table('users')->insert(['name' => '张三']);
     *       PandaDB::table('logs')->insert(['action' => 'create_user']);
     *   });
     */
    public static function transaction(callable $callback)
    {
        self::beginTransaction();
        try {
            $result = $callback();
            self::commit();
            return $result;
        } catch (\Exception $e) {
            self::rollback();
            throw $e;
        } catch (\Throwable $e) {
            self::rollback();
            throw $e;
        }
    }

    /**
     * 检查当前是否在事务中
     *
     * @return bool
     */
    public static function inTransaction(): bool
    {
        return self::$pdo !== null && self::$pdo->inTransaction();
    }

    // ==================== 十二、Medoo 风格静态快捷方法 ====================

    /**
     * 查询多条记录（Medoo 风格）
     *
     * @param string $table 表名
     * @param array $columns 查询字段，默认 ['*']
     * @param array $where WHERE 条件（支持数组形式）
     * @return array 结果集
     *
     * 示例：
     *   PandaDB::tableSelect('users', ['id', 'name'], ['status' => 'active', 'age[>]' => 18]);
     */
    public static function tableSelect(string $table, array $columns = ['*'], array $where = []): array
    {
        $query = self::table($table);

        if ($columns !== ['*']) {
            $query->field($columns);
        }

        if (!empty($where)) {
            self::applyMedooWhere($query, $where);
        }

        return $query->select();
    }

    /**
     * 查询单条记录（Medoo 风格）
     *
     * @param string $table 表名
     * @param array $columns 查询字段
     * @param array $where WHERE 条件
     * @return array|null 单条记录或 null
     */
    public static function get(string $table, array $columns = ['*'], array $where = []): ?array
    {
        $query = self::table($table);

        if ($columns !== ['*']) {
            $query->field($columns);
        }

        if (!empty($where)) {
            self::applyMedooWhere($query, $where);
        }

        return $query->find();
    }

    /**
     * 插入数据（Medoo 风格）
     *
     * @param string $table 表名
     * @param array $data 键值对数据
     * @return int 最后插入的 ID
     */
    public static function tableInsert(string $table, array $data): int
    {
        return self::table($table)->insert($data);
    }

    /**
     * 批量插入数据（Medoo 风格）
     *
     * @param string $table 表名
     * @param array $dataSet 二维数组
     * @return int 受影响的行数
     */
    public static function tableInsertAll(string $table, array $dataSet): int
    {
        return self::table($table)->insertAll($dataSet);
    }

    /**
     * 更新数据（Medoo 风格）
     *
     * @param string $table 表名
     * @param array $data 键值对数据
     * @param array $where WHERE 条件
     * @return int 受影响的行数
     */
    public static function tableUpdate(string $table, array $data, array $where = []): int
    {
        $query = self::table($table);
        if (!empty($where)) {
            self::applyMedooWhere($query, $where);
        }
        return $query->update($data);
    }

    /**
     * 删除数据（Medoo 风格）
     *
     * @param string $table 表名
     * @param array $where WHERE 条件
     * @return int 受影响的行数
     */
    public static function tableDelete(string $table, array $where = []): int
    {
        $query = self::table($table);
        if (!empty($where)) {
            self::applyMedooWhere($query, $where);
        }
        return $query->delete();
    }

    /**
     * REPLACE INTO 替换插入（Medoo 风格）
     *
     * 如果记录已存在（主键或唯一索引冲突），则先删除再插入。
     *
     * @param string $table 表名
     * @param array $data 键值对数据
     * @return int 受影响的行数
     */
    public static function replace(string $table, array $data): int
    {
        $columns = array_keys($data);
        $values = array_values($data);

        $columnStr = implode(', ', array_map(function ($col) {
            if (self::$driver === 'pgsql') {
                return '"' . $col . '"';
            }
            return '`' . $col . '`';
        }, $columns));

        $placeholders = implode(', ', array_fill(0, count($values), '?'));

        $sql = "REPLACE INTO " . self::wrapTableName($table) . " ({$columnStr}) VALUES ({$placeholders})";

        return self::executeUpdate($sql, $values);
    }

    /**
     * 统计记录数（Medoo 风格）
     *
     * @param string $table 表名
     * @param array $where WHERE 条件
     * @return int
     */
    public static function tableCount(string $table, array $where = []): int
    {
        $query = self::table($table);
        if (!empty($where)) {
            self::applyMedooWhere($query, $where);
        }
        return $query->count();
    }

    /**
     * 求和（Medoo 风格）
     *
     * @param string $table 表名
     * @param string $column 字段名
     * @param array $where WHERE 条件
     * @return float
     */
    public static function tableSum(string $table, string $column, array $where = []): float
    {
        $query = self::table($table);
        if (!empty($where)) {
            self::applyMedooWhere($query, $where);
        }
        return $query->sum($column);
    }

    /**
     * 求平均值（Medoo 风格）
     *
     * @param string $table 表名
     * @param string $column 字段名
     * @param array $where WHERE 条件
     * @return float
     */
    public static function tableAvg(string $table, string $column, array $where = []): float
    {
        $query = self::table($table);
        if (!empty($where)) {
            self::applyMedooWhere($query, $where);
        }
        return $query->avg($column);
    }

    /**
     * 求最大值（Medoo 风格）
     *
     * @param string $table 表名
     * @param string $column 字段名
     * @param array $where WHERE 条件
     * @return float
     */
    public static function tableMax(string $table, string $column, array $where = []): float
    {
        $query = self::table($table);
        if (!empty($where)) {
            self::applyMedooWhere($query, $where);
        }
        return $query->max($column);
    }

    /**
     * 求最小值（Medoo 风格）
     *
     * @param string $table 表名
     * @param string $column 字段名
     * @param array $where WHERE 条件
     * @return float
     */
    public static function tableMin(string $table, string $column, array $where = []): float
    {
        $query = self::table($table);
        if (!empty($where)) {
            self::applyMedooWhere($query, $where);
        }
        return $query->min($column);
    }

    /**
     * 检查表是否存在（Medoo 风格）
     *
     * @param string $table 表名
     * @return bool
     */
    public static function has(string $table): bool
    {
        $driver = self::$driver;
        $database = self::$config['database'] ?? '';

        switch ($driver) {
            case 'mysql':
                $sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = ? AND table_name = ?";
                $result = self::query($sql, [$database, $table]);
                return !empty($result);

            case 'pgsql':
                $sql = "SELECT 1 FROM information_schema.tables WHERE table_catalog = ? AND table_name = ?";
                $result = self::query($sql, [$database, $table]);
                return !empty($result);

            case 'sqlite':
                $sql = "SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = ?";
                $result = self::query($sql, [$table]);
                return !empty($result);

            default:
                throw new DbException("不支持的数据库驱动类型: {$driver}");
        }
    }

    /**
     * 创建表（Medoo 风格）
     *
     * @param string $table 表名
     * @param array $columns 字段定义，格式：['字段名' => '类型定义', ...]
     * @param array $indexes 索引定义，格式：['PRIMARY KEY (id)', 'INDEX idx_name (name)', ...]
     * @return bool 是否成功
     *
     * 示例：
     *   PandaDB::create('users', [
     *       'id'       => 'INT AUTO_INCREMENT PRIMARY KEY',
     *       'name'     => 'VARCHAR(100) NOT NULL',
     *       'email'    => 'VARCHAR(200) NOT NULL UNIQUE',
     *       'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
     *   ], [
     *       'INDEX idx_name (name)',
     *   ]);
     */
    public static function create(string $table, array $columns, array $indexes = []): bool
    {
        $columnDefs = [];
        foreach ($columns as $name => $definition) {
            $columnDefs[] = self::wrapIdentifierStatic($name) . ' ' . $definition;
        }

        $sql = "CREATE TABLE IF NOT EXISTS " . self::wrapTableName($table) . " (";
        $sql .= implode(', ', $columnDefs);

        if (!empty($indexes)) {
            $sql .= ', ' . implode(', ', $indexes);
        }

        $sql .= ')';

        return self::execute($sql) >= 0;
    }

    /**
     * 删除表（Medoo 风格）
     *
     * @param string $table 表名
     * @return bool 是否成功
     */
    public static function drop(string $table): bool
    {
        $sql = "DROP TABLE IF EXISTS " . self::wrapTableName($table);
        return self::execute($sql) >= 0;
    }

    /**
     * 清空表数据（Medoo 风格）
     *
     * @param string $table 表名
     * @return bool 是否成功
     */
    public static function truncate(string $table): bool
    {
        $driver = self::$driver;

        if ($driver === 'sqlite') {
            // SQLite 不支持 TRUNCATE，使用 DELETE + VACUUM 替代
            self::execute("DELETE FROM " . self::wrapTableName($table));
            self::execute("VACUUM");
            return true;
        }

        $sql = "TRUNCATE TABLE " . self::wrapTableName($table);
        return self::execute($sql) >= 0;
    }

    // ==================== 十三、Medoo WHERE 条件解析器 ====================

    /**
     * 将 Medoo 风格的 WHERE 数组应用到查询构建器
     *
     * 支持的 Medoo 语法：
     *   - ['field' => value]              → field = value
     *   - ['field[>]' => value]           → field > value
     *   - ['field[<]' => value]           → field < value
     *   - ['field[>=]' => value]          → field >= value
     *   - ['field[<=]' => value]          → field <= value
     *   - ['field[!=]' => value]          → field != value
     *   - ['field[<>]' => value]          → field <> value
     *   - ['field[<>]' => [a, b]]         → field NOT BETWEEN a AND b
     *   - ['field[><]' => [a, b]]         → field BETWEEN a AND b
     *   - ['field[]' => [a, b, c]]        → field IN (a, b, c)
     *   - ['field[!]' => [a, b, c]]       → field NOT IN (a, b, c)
     *   - ['field[~]' => '%value%']       → field LIKE '%value%'
     *   - ['field[!~]' => '%value%']      → field NOT LIKE '%value%'
     *   - ['field[null]' => true]         → field IS NULL
     *   - ['field[not null]' => true]     → field IS NOT NULL
     *   - ['GROUP' => [...]]              → 嵌套条件组 (AND)
     *   - ['OR' => [...]]                 → OR 条件组
     *   - ['ORDER' => 'field ASC']        → 排序
     *   - ['LIMIT' => 10]                 → 限制
     *   - ['#comment' => '...']           → 注释（忽略）
     *
     * @param self $query 查询构建器实例
     * @param array $where Medoo 风格的 WHERE 数组
     * @return void
     */
    protected static function applyMedooWhere(self $query, array $where): void
    {
        foreach ($where as $key => $value) {
            // 特殊键：GROUP（AND 嵌套组）
            if ($key === 'GROUP' && is_array($value)) {
                $query->where(function (self $q) use ($value) {
                    self::applyMedooWhere($q, $value);
                });
                continue;
            }

            // 特殊键：OR（OR 条件组）
            if ($key === 'OR' && is_array($value)) {
                $query->whereOr(function (self $q) use ($value) {
                    self::applyMedooWhere($q, $value);
                });
                continue;
            }

            // 特殊键：ORDER
            if ($key === 'ORDER') {
                $query->orderRaw($value);
                continue;
            }

            // 特殊键：LIMIT
            if ($key === 'LIMIT') {
                $query->limit((int) $value);
                continue;
            }

            // 特殊键：注释（以 # 开头的键忽略）
            if (strpos($key, '#') === 0) {
                continue;
            }

            // 解析字段和操作符
            $parsed = self::parseMedooCondition($key, $value);

            if ($parsed !== null) {
                switch ($parsed['type']) {
                    case 'basic':
                        $query->where($parsed['field'], $parsed['operator'], $parsed['value']);
                        break;
                    case 'in':
                        if ($parsed['operator'] === 'IN') {
                            $query->whereIn($parsed['field'], $parsed['value']);
                        } else {
                            $query->whereNotIn($parsed['field'], $parsed['value']);
                        }
                        break;
                    case 'between':
                        if ($parsed['operator'] === 'BETWEEN') {
                            $query->whereBetween($parsed['field'], $parsed['value'][0], $parsed['value'][1]);
                        } else {
                            $query->whereNotBetween($parsed['field'], $parsed['value'][0], $parsed['value'][1]);
                        }
                        break;
                    case 'like':
                        $query->where($parsed['field'], $parsed['operator'], $parsed['value']);
                        break;
                    case 'null':
                        if ($parsed['operator'] === 'IS NULL') {
                            $query->whereNull($parsed['field']);
                        } else {
                            $query->whereNotNull($parsed['field']);
                        }
                        break;
                }
            }
        }
    }

    /**
     * 解析 Medoo 风格的条件键值对
     *
     * @param string $key 条件键（可能包含操作符后缀）
     * @param mixed $value 条件值
     * @return array|null 解析后的条件数组，或 null（无法解析时）
     */
    protected static function parseMedooCondition(string $key, $value): ?array
    {
        // 匹配 field[operator] 格式
        if (preg_match('/^([a-zA-Z0-9_.]+)\[(.+)\]$/', $key, $matches)) {
            $field = $matches[1];
            $operator = $matches[2];

            switch ($operator) {
                // 比较操作符
                case '>':
                case '<':
                case '>=':
                case '<=':
                case '!=':
                case '<>':
                    return ['type' => 'basic', 'field' => $field, 'operator' => $operator, 'value' => $value];

                // IN / NOT IN
                case '':
                    return ['type' => 'in', 'field' => $field, 'operator' => 'IN', 'value' => (array) $value];
                case '!':
                    return ['type' => 'in', 'field' => $field, 'operator' => 'NOT IN', 'value' => (array) $value];

                // BETWEEN / NOT BETWEEN
                case '><':
                    return ['type' => 'between', 'field' => $field, 'operator' => 'BETWEEN', 'value' => (array) $value];
                case '<>':
                    return ['type' => 'between', 'field' => $field, 'operator' => 'NOT BETWEEN', 'value' => (array) $value];

                // LIKE / NOT LIKE
                case '~':
                    return ['type' => 'like', 'field' => $field, 'operator' => 'LIKE', 'value' => $value];
                case '!~':
                    return ['type' => 'like', 'field' => $field, 'operator' => 'NOT LIKE', 'value' => $value];

                // NULL / NOT NULL
                case 'null':
                    return ['type' => 'null', 'field' => $field, 'operator' => 'IS NULL'];
                case 'not null':
                    return ['type' => 'null', 'field' => $field, 'operator' => 'IS NOT NULL'];

                default:
                    return null;
            }
        }

        // 纯字段名，等值比较
        return ['type' => 'basic', 'field' => $key, 'operator' => '=', 'value' => $value];
    }

    /**
     * 静态方法包装表名（用于 Medoo 风格方法）
     *
     * @param string $table 表名
     * @return string
     */
    protected static function wrapTableName(string $table): string
    {
        if (self::$driver === 'pgsql') {
            return '"' . str_replace('"', '""', $table) . '"';
        }
        return '`' . str_replace('`', '``', $table) . '`';
    }

    /**
     * 静态方法包装标识符（用于 Medoo 风格方法）
     *
     * @param string $identifier 标识符
     * @return string
     */
    protected static function wrapIdentifierStatic(string $identifier): string
    {
        if (self::$driver === 'pgsql') {
            return '"' . str_replace('"', '""', $identifier) . '"';
        }
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    // ==================== 十四、调试统计方法 ====================

    /**
     * 获取所有查询日志
     *
     * 每条日志包含 sql、bindings、time 三个字段。
     *
     * @return array
     */
    public static function getQueryLog(): array
    {
        return self::$queryLog;
    }

    /**
     * 获取最后执行的一条 SQL
     *
     * @return string|null SQL 语句，无查询记录时返回 null
     */
    public static function getLastSql(): ?string
    {
        if (empty(self::$queryLog)) {
            return null;
        }
        $last = end(self::$queryLog);
        return $last['sql'] ?? null;
    }

    /**
     * 获取最后执行的一条 SQL 及其绑定参数
     *
     * @return array|null 包含 sql 和 bindings 的数组
     */
    public static function getLastQuery(): ?array
    {
        if (empty(self::$queryLog)) {
            return null;
        }
        return end(self::$queryLog);
    }

    /**
     * 获取查询总次数
     *
     * @return int
     */
    public static function getQueryCount(): int
    {
        return self::$queryCount;
    }

    /**
     * 获取所有查询的总耗时（毫秒）
     *
     * @return float
     */
    public static function getTotalQueryTime(): float
    {
        return self::$totalTime;
    }

    /**
     * 获取所有慢查询记录
     *
     * 慢查询是指执行时间超过阈值（默认 1000ms）的查询。
     *
     * @return array
     */
    public static function getSlowQueries(): array
    {
        return self::$slowQueries;
    }

    /**
     * 设置慢查询阈值（毫秒）
     *
     * @param float $threshold 阈值，单位毫秒
     * @return void
     */
    public static function setSlowThreshold(float $threshold): void
    {
        self::$slowThreshold = $threshold;
    }

    /**
     * 获取最后插入的 ID
     *
     * @return string
     */
    public static function getLastInsertId(): string
    {
        return self::getPdo()->lastInsertId();
    }

    /**
     * 清空所有查询日志和统计
     *
     * @return void
     */
    public static function clearQueryLog(): void
    {
        self::$queryLog = [];
        self::$queryCount = 0;
        self::$totalTime = 0.0;
        self::$slowQueries = [];
    }

    // ==================== 十五、查询日志记录 ====================

    /**
     * 记录一条查询日志
     *
     * 自动统计查询次数、总耗时，并检测慢查询。
     *
     * @param string $sql SQL 语句
     * @param array $bindings 绑定参数
     * @param float $time 执行时间（毫秒）
     * @return void
     */
    protected static function logQuery(string $sql, array $bindings, float $time): void
    {
        self::$queryCount++;
        self::$totalTime += $time;

        $log = [
            'sql'      => $sql,
            'bindings' => $bindings,
            'time'     => round($time, 4),
        ];

        self::$queryLog[] = $log;

        // 检测慢查询
        if ($time > self::$slowThreshold) {
            self::$slowQueries[] = $log;
        }
    }
}

// ==================== JoinClause 辅助类 ====================

/**
 * JOIN 子句构建器
 *
 * 用于支持闭包形式的复杂 JOIN 条件。
 *
 * @package PandaAPI\Database
 */
class JoinClause
{
    /** @var string JOIN 类型 */
    protected string $type;

    /** @var string 关联表名 */
    protected string $table;

    /** @var array ON 条件栈 */
    protected array $conditions = [];

    /** @var array 绑定参数 */
    protected array $bindings = [];

    /**
     * 构造函数
     *
     * @param string $table 表名
     * @param string $type JOIN 类型
     */
    public function __construct(string $table, string $type)
    {
        $this->table = $table;
        $this->type = $type;
    }

    /**
     * 添加 ON 条件
     *
     * @param string $first 第一个字段
     * @param string $operator 操作符（默认 =）
     * @param string|null $second 第二个字段（可选，省略时使用 = 操作符）
     * @return $this
     *
     * 示例：
     *   $join->on('users.id', 'orders.user_id')
     *   $join->on('users.id', '=', 'orders.user_id')
     */
    public function on(string $first, string $operator, ?string $second = null): self
    {
        if ($second === null) {
            $second = $operator;
            $operator = '=';
        }

        $this->conditions[] = [
            'type'     => 'basic',
            'first'    => $first,
            'operator' => $operator,
            'second'   => $second,
            'logic'    => 'AND',
        ];

        return $this;
    }

    /**
     * 添加 OR ON 条件
     *
     * @param string $first 第一个字段
     * @param string $operator 操作符
     * @param string|null $second 第二个字段
     * @return $this
     */
    public function orOn(string $first, string $operator, ?string $second = null): self
    {
        if ($second === null) {
            $second = $operator;
            $operator = '=';
        }

        $this->conditions[] = [
            'type'     => 'basic',
            'first'    => $first,
            'operator' => $operator,
            'second'   => $second,
            'logic'    => 'OR',
        ];

        return $this;
    }

    /**
     * 添加 WHERE 条件到 JOIN
     *
     * @param string $column 字段名
     * @param mixed $operator 操作符或值
     * @param mixed $value 值
     * @return $this
     */
    public function where(string $column, $operator, $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->conditions[] = [
            'type'     => 'where',
            'column'   => $column,
            'operator' => strtoupper($operator),
            'value'    => $value,
            'logic'    => 'AND',
        ];

        return $this;
    }

    /**
     * 添加 OR WHERE 条件到 JOIN
     *
     * @param string $column 字段名
     * @param mixed $operator 操作符或值
     * @param mixed $value 值
     * @return $this
     */
    public function orWhere(string $column, $operator, $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->conditions[] = [
            'type'     => 'where',
            'column'   => $column,
            'operator' => strtoupper($operator),
            'value'    => $value,
            'logic'    => 'OR',
        ];

        return $this;
    }

    /**
     * 构建 JOIN SQL 字符串
     *
     * @param PandaDB $query 主查询构建器（用于获取标识符包装方法）
     * @return string
     */
    public function toSql(PandaDB $query): string
    {
        $table = $query->wrapField($this->table);
        $sql = "{$this->type} JOIN {$table} ON ";

        $parts = [];
        foreach ($this->conditions as $index => $condition) {
            $part = $this->buildCondition($condition, $query);
            if ($index === 0) {
                $parts[] = $part;
            } else {
                $parts[] = $condition['logic'] . ' ' . $part;
            }
        }

        $sql .= implode(' ', $parts);

        // 将绑定参数合并到主查询
        if (!empty($this->bindings)) {
            // 通过反射或直接访问来合并绑定参数
            $reflection = new \ReflectionClass($query);
            $prop = $reflection->getProperty('bindings');
            $prop->setAccessible(true);
            $currentBindings = $prop->getValue($query);
            $prop->setValue($query, array_merge($currentBindings, $this->bindings));
        }

        return $sql;
    }

    /**
     * 构建单个条件
     *
     * @param array $condition 条件定义
     * @param PandaDB $query 主查询构建器
     * @return string
     */
    protected function buildCondition(array $condition, PandaDB $query): string
    {
        if ($condition['type'] === 'basic') {
            $first = $query->wrapField($condition['first']);
            $second = $query->wrapField($condition['second']);
            return "{$first} {$condition['operator']} {$second}";
        }

        if ($condition['type'] === 'where') {
            $column = $query->wrapField($condition['column']);
            $this->bindings[] = $condition['value'];
            return "{$column} {$condition['operator']} ?";
        }

        return '';
    }
}
