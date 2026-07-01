<?php
/**
 * 数据库连接管理
 * 封装PDO操作，支持连接池
 */

namespace QuickAPI\Database;

use PDO;
use PDOException;
use QuickAPI\Database\Pool\DBConnectionPool;
use QuickAPI\Exception\DbException;

class Connection
{
    /**
     * @var Connection 单例实例
     */
    protected static $instance;

    /**
     * @var array 配置
     */
    protected $config;

    /**
     * @var PDO|null PDO连接
     */
    protected $pdo;

    /**
     * @var DBConnectionPool|null 连接池
     */
    protected $pool;

    /**
     * @var bool 是否使用连接池
     */
    protected $usePool = true;

    /**
     * @var array 查询统计
     */
    protected $queryStats = [
        'count' => 0,
        'total_time' => 0,
        'slow_queries' => [],
        'queries' => []
    ];

    /**
     * @var float 慢查询阈值(ms)
     */
    protected $slowQueryThreshold = 100;

    /**
     * 私有构造函数
     */
    private function __construct()
    {
    }

    /**
     * 获取单例实例
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 配置数据库连接
     */
    public function configure(array $config): void
    {
        $this->config = $config;
        
        // 配置连接池
        if ($this->usePool && isset($config['pool'])) {
            $this->pool = DBConnectionPool::getInstance();
            $this->pool->configure($config);
        }
    }

    /**
     * 获取PDO连接
     */
    public function getPdo(): PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        if ($this->usePool && $this->pool !== null) {
            return $this->pool->getConnection();
        }

        return $this->createConnection();
    }

    /**
     * 释放PDO连接（连接池模式）
     */
    public function releasePdo(PDO $pdo): void
    {
        if ($this->usePool && $this->pool !== null) {
            $this->pool->releaseConnection($pdo);
        }
    }

    /**
     * 创建新的数据库连接
     */
    protected function createConnection(): PDO
    {
        $dsn = $this->buildDsn();
        
        try {
            $this->pdo = new PDO(
                $dsn,
                $this->config['username'] ?? 'root',
                $this->config['password'] ?? '',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => $this->config['timeout'] ?? 5,
                ]
            );
            
            // 设置字符集
            if (isset($this->config['charset'])) {
                $this->pdo->exec("SET NAMES {$this->config['charset']}");
            }
            
            return $this->pdo;
        } catch (PDOException $e) {
            throw new DbException("Database connection failed: " . $e->getMessage(), 500, $e);
        }
    }

    /**
     * 构建DSN字符串
     */
    protected function buildDsn(): string
    {
        $driver = $this->config['driver'] ?? 'mysql';
        $host = $this->config['host'] ?? '127.0.0.1';
        $port = $this->config['port'] ?? 3306;
        $database = $this->config['database'] ?? '';
        $charset = $this->config['charset'] ?? 'utf8mb4';

        switch ($driver) {
            case 'mysql':
                return "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
            case 'pgsql':
                return "pgsql:host={$host};port={$port};dbname={$database}";
            case 'sqlite':
                return "sqlite:{$database}";
            case 'sqlsrv':
                return "sqlsrv:Server={$host},{$port};Database={$database}";
            default:
                throw new DbException("Unsupported database driver: {$driver}", 500);
        }
    }

    /**
     * 记录查询统计
     */
    public function recordQuery(string $sql, float $executionTime): void
    {
        $this->queryStats['count']++;
        $this->queryStats['total_time'] += $executionTime;
        
        $this->queryStats['queries'][] = [
            'sql' => $sql,
            'time' => $executionTime,
            'timestamp' => microtime(true)
        ];
        
        // 记录慢查询
        if ($executionTime > $this->slowQueryThreshold) {
            $this->queryStats['slow_queries'][] = [
                'sql' => $sql,
                'time' => $executionTime,
                'timestamp' => microtime(true)
            ];
        }
        
        // 保持最近100条查询记录
        if (count($this->queryStats['queries']) > 100) {
            array_shift($this->queryStats['queries']);
        }
    }

    /**
     * 获取查询统计
     */
    public function getQueryStats(): array
    {
        return [
            'count' => $this->queryStats['count'],
            'total_time' => $this->queryStats['total_time'],
            'avg_time' => $this->queryStats['count'] > 0 
                ? $this->queryStats['total_time'] / $this->queryStats['count'] 
                : 0,
            'slow_queries_count' => count($this->queryStats['slow_queries']),
            'slow_queries' => $this->queryStats['slow_queries'],
            'queries' => $this->queryStats['queries']
        ];
    }

    /**
     * 获取查询次数
     */
    public function getQueryCount(): int
    {
        return $this->queryStats['count'];
    }

    /**
     * 获取总查询时间
     */
    public function getTotalQueryTime(): float
    {
        return $this->queryStats['total_time'];
    }

    /**
     * 获取最后查询时间
     */
    public function getLastQueryTime(): float
    {
        $queries = $this->queryStats['queries'];
        return !empty($queries) ? end($queries)['time'] : 0;
    }

    /**
     * 获取慢查询列表
     */
    public function getSlowQueries(): array
    {
        return $this->queryStats['slow_queries'];
    }

    /**
     * 设置慢查询阈值
     */
    public function setSlowQueryThreshold(float $threshold): void
    {
        $this->slowQueryThreshold = $threshold;
    }

    /**
     * 重置统计
     */
    public function resetStats(): void
    {
        $this->queryStats = [
            'count' => 0,
            'total_time' => 0,
            'slow_queries' => [],
            'queries' => []
        ];
    }

    /**
     * 是否启用连接池
     */
    public function usePool(bool $use): void
    {
        $this->usePool = $use;
    }

    /**
     * 关闭连接
     */
    public function close(): void
    {
        if ($this->pool !== null) {
            $this->pool->closeAll();
        }
        
        if ($this->pdo !== null) {
            $this->pdo = null;
        }
    }

    /**
     * 开始事务
     */
    public function beginTransaction(): bool
    {
        return $this->getPdo()->beginTransaction();
    }

    /**
     * 提交事务
     */
    public function commit(): bool
    {
        return $this->getPdo()->commit();
    }

    /**
     * 回滚事务
     */
    public function rollback(): bool
    {
        return $this->getPdo()->rollBack();
    }

    /**
     * 执行事务
     */
    public function transaction(callable $callback)
    {
        $this->beginTransaction();
        
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * 获取配置
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
