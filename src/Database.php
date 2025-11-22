<?php

namespace FiefdomForge;

use PDO;
use PDOException;

class Database
{
    private static ?Database $instance = null;
    private ?PDO $connection = null;
    private Config $config;

    private function __construct()
    {
        $this->config = Config::getInstance();
        $this->connect();
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    private function connect(): void
    {
        $dbPath = $this->config->get('db.path');

        // Ensure database directory exists
        $dbDir = dirname($dbPath);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        $dsn = "sqlite:{$dbPath}";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->connection = new PDO($dsn, null, null, $options);

            // Enable foreign keys for SQLite
            $this->connection->exec('PRAGMA foreign_keys = ON');
        } catch (PDOException $e) {
            if ($this->config->isDebug()) {
                throw new PDOException("Database connection failed: " . $e->getMessage());
            }
            throw new PDOException("Database connection failed");
        }
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));

        return (int)$this->connection->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(' = ?, ', array_keys($data)) . ' = ?';
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";

        $params = array_merge(array_values($data), $whereParams);
        $stmt = $this->query($sql, $params);

        return $stmt->rowCount();
    }

    public function delete(string $table, string $where, array $params = []): int
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->connection->commit();
    }

    public function rollback(): bool
    {
        return $this->connection->rollBack();
    }

    /**
     * Initialize the database schema
     */
    public function initializeSchema(): void
    {
        $schemaPath = $this->config->get('paths.root') . '/sql/schema.sql';

        if (!file_exists($schemaPath)) {
            throw new \RuntimeException("Schema file not found: {$schemaPath}");
        }

        $schema = file_get_contents($schemaPath);

        // Split by semicolon and execute each statement
        // Note: SQLite handles -- comments fine, so we only filter empty statements
        $statements = array_filter(
            array_map('trim', explode(';', $schema)),
            fn($s) => !empty($s)
        );

        foreach ($statements as $statement) {
            if (!empty(trim($statement))) {
                $this->connection->exec($statement);
            }
        }
    }

    /**
     * Check if the database has been initialized
     */
    public function isInitialized(): bool
    {
        try {
            $result = $this->fetch(
                "SELECT name FROM sqlite_master WHERE type='table' AND name='users'"
            );
            return $result !== null;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Ensure notifications table exists (migration)
     */
    public function ensureNotificationsTable(): void
    {
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS notifications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                event_id INTEGER NOT NULL,
                is_read INTEGER DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (event_id) REFERENCES game_events(id),
                UNIQUE(user_id, event_id)
            )
        ");
        $this->connection->exec("CREATE INDEX IF NOT EXISTS idx_notifications_user_id ON notifications(user_id)");
        $this->connection->exec("CREATE INDEX IF NOT EXISTS idx_notifications_is_read ON notifications(is_read)");
    }
}
