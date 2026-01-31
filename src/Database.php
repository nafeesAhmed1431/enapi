<?php

namespace NafeesAhmed\Enapi;

use PDO;
use PDOException;

class Database
{
    protected ?PDO $pdo = null;
    protected array $config;

    /**
     * Constructor
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->connect();
    }

    /**
     * Create database connection
     */
    protected function connect(): void
    {
        try {
            $driver = $this->config['driver'] ?? 'mysql';
            $host = $this->config['host'] ?? 'localhost';
            $port = $this->config['port'] ?? 3306;
            $database = $this->config['database'] ?? '';
            $username = $this->config['username'] ?? 'root';
            $password = $this->config['password'] ?? '';
            $charset = $this->config['charset'] ?? 'utf8mb4';

            $dsn = "$driver:host=$host;port=$port;dbname=$database;charset=$charset";
            
            $this->pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Execute a query and return results
     */
    public function query(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new \Exception("Query failed: " . $e->getMessage());
        }
    }

    /**
     * Execute a statement (INSERT, UPDATE, DELETE)
     */
    public function execute(string $sql, array $params = []): int
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            // Return last insert ID for INSERT, affected rows for UPDATE/DELETE
            return $this->pdo->lastInsertId() ?: $stmt->rowCount();
        } catch (PDOException $e) {
            throw new \Exception("Execution failed: " . $e->getMessage());
        }
    }

    /**
     * Get PDO instance
     */
    public function getPdo(): ?PDO
    {
        return $this->pdo;
    }

    /**
     * Begin transaction
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }
}
