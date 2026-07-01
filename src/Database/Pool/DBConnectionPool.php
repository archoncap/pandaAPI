<?php
/**
 * 数据库连接池管理器
 * 减少数据库连接开销，提升性能
 */

namespace QuickAPI\Database\Pool;

use PDO;
use PDOException;
use QuickAPI\Exception\DbException;

class DBConnectionPool
{
    /**
     * @var DBConnectionPool 单例实例
     */
    protected static $instance;

    /**
     * @var array 连接配置
     */
    protected $config;

    /**
     * @var array 可用连接池
     */
    protected $availableConnections = [];

    /**
     * @var array 正在使用的连接
     */
    protected $inUseConnections = [];

    /**
     * @var int 最大连接数
     */
    protected $maxConnections = 10;

    /**
     * @var int 最小连接数
     */
    protected $minConnections = 2;

    /**
     * @var int 空闲超时时间（秒）
     */
    protected $idleTimeout = 60;

    /**
     * @var int 连接超时时间（秒）
     */
    protected $connectTimeout = 5;

    /**
     * @var int 当前连接数
     */
    protected $currentConnections = 0;

    /**
     * @var bool 池是否已初始化
     */
    protected $initialized = false;

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
     * 配置连接池
     */
    public function configure(array $config): void
    {
        $this->config = $config;
        
        if (isset($config['pool'])) {
            $this->maxConnections = $config['pool']['max'] ?? 10;
            $this->minConnections = $config['pool']['min'] ?? 2;
            $this->idleTimeout = $config['pool']['idle_timeout'] ?? 60;
            $this->connectTimeout = $config['pool']['connect_timeout'] ?? 5;
        }
    }

    /**
     * 初始化连接池
     */
    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        // 创建最小连接数
        for ($i = 0; $i < $this->minConnections; $i++) {
            $this->availableConnections[] = $this->createConnection();
            $this->currentConnections++;
        }

        $this->initialized = true;
    }

    /**
     * 获取连接
     */
    public function getConnection(): PDO
    {
        // 初始化检查
        if (!$this->initialized) {
            $this->initialize();
        }

        // 先检查空闲连接
        if (!empty($this->availableConnections)) {
            $connection = array_pop($this->availableConnections);
            
            // 验证连接是否有效
            if ($this->isConnectionValid($connection)) {
                $this->inUseConnections[] = $connection;
                return $connection;
            }
            
            // 连接无效，重新创建
            $this->currentConnections--;
        }

        // 检查是否可以达到最大连接数
        if ($this->currentConnections < $this->maxConnections) {
            $connection = $this->createConnection();
            $this->inUseConnections[] = $connection;
            $this->currentConnections++;
            return $connection;
        }

        // 等待可用连接（轮询方式）
        return $this->waitForConnection();
    }

    /**
     * 释放连接回连接池
     */
    public function releaseConnection(PDO $connection): void
    {
        foreach ($this->inUseConnections as $key => $conn) {
            if ($conn === $connection) {
                unset($this->inUseConnections[$key]);
                $this->inUseConnections = array_values($this->inUseConnections);
                
                // 如果连接有效，放回可用池
                if ($this->isConnectionValid($connection)) {
                    $this->availableConnections[] = $connection;
                } else {
                    $this->currentConnections--;
                }
                return;
            }
        }
    }

    /**
     * 关闭所有连接
     */
    public function closeAll(): void
    {
        foreach ($this->availableConnections as $conn) {
            $this->closeConnection($conn);
        }
        
        foreach ($this->inUseConnections as $conn) {
            $this->closeConnection($conn);
        }

        $this->availableConnections = [];
        $this->inUseConnections = [];
        $this->currentConnections = 0;
        $this->initialized = false;
    }

    /**
     * 获取连接统计信息
     */
    public function getStats(): array
    {
        return [
            'max_connections' => $this->maxConnections,
            'min_connections' => $this->minConnections,
            'current_connections' => $this->currentConnections,
            'available' => count($this->availableConnections),
            'in_use' => count($this->inUseConnections),
        ];
    }

    /**
     * 创建新连接
     */
    protected function createConnection(): PDO
    {
        $dsn = $this->buildDsn();
        
        try {
            $connection = new PDO(
                $dsn,
                $this->config['username'] ?? 'root',
                $this->config['password'] ?? '',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => $this->connectTimeout,
                ]
            );
            
            // 设置字符集
            if (isset($this->config['charset'])) {
                $connection->exec("SET NAMES {$this->config['charset']}");
            }
            
            return $connection;
        } catch (PDOException $e) {
            throw new DbException("Database connection failed: " . $e->getMessage(), 500);
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
     * 验证连接是否有效
     */
    protected function isConnectionValid(PDO $connection): bool
    {
        try {
            $connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * 等待可用连接
     */
    protected function waitForConnection(): PDO
    {
        $maxWait = 30; // 最大等待30秒
        $waited = 0;
        $sleepTime = 0.01; // 10毫秒

        while ($waited < $maxWait) {
            if (!empty($this->availableConnections)) {
                $connection = array_pop($this->availableConnections);
                if ($this->isConnectionValid($connection)) {
                    $this->inUseConnections[] = $connection;
                    return $connection;
                }
                $this->currentConnections--;
            }

            usleep($sleepTime * 1000000);
            $waited += $sleepTime;
            $sleepTime = min($sleepTime * 1.5, 0.5); // 指数退避，最大500ms
        }

        throw new DbException("Connection pool timeout: all connections in use", 503);
    }

    /**
     * 关闭单个连接
     */
    protected function closeConnection(PDO $connection): void
    {
        try {
            $connection = null;
        } catch (\Exception $e) {
            // 忽略关闭错误
        }
    }

    /**
     * 清理空闲连接
     */
    public function pruneIdleConnections(): void
    {
        $keep = $this->minConnections;
        
        while (count($this->availableConnections) > $keep) {
            $connection = array_pop($this->availableConnections);
            $this->closeConnection($connection);
            $this->currentConnections--;
        }
    }
}
