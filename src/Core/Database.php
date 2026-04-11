<?php

namespace App\Core;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Result;

class Database
{
    private static ?Database $instance = null;
    private Connection $connection;

    private function __construct(string $path)
    {
        $connectionParams = [
            'driver' => 'pdo_sqlite',
            'path' => $path,
        ];
        $this->connection = DriverManager::getConnection($connectionParams);
        $this->connection->executeStatement('PRAGMA foreign_keys = ON');
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            $dbPath = dirname(__DIR__, 2) . '/database/app.db';
            self::$instance = new self($dbPath);
        }
        return self::$instance;
    }

    public static function init(): void
    {
        $db = self::getInstance();
        $schema = file_get_contents(dirname(__DIR__, 2) . '/database/schema.sql');
        $db->connection->executeStatement($schema);

        // Migrations
        $columns = array_column(
            $db->connection->executeQuery('PRAGMA table_info(users)')->fetchAllAssociative(),
            'name'
        );
        if (!in_array('avatar', $columns)) {
            $db->connection->executeStatement('ALTER TABLE users ADD COLUMN avatar TEXT');
        }
    }

    public function query(string $sql, array $params = []): Result
    {
        return $this->connection->executeQuery($sql, $params);
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetchAssociative();
        return $result ?: null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAllAssociative();
    }

    public function lastInsertId(): int
    {
        return (int) $this->connection->lastInsertId();
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function executeStatement(string $sql, array $params = []): int
    {
        return $this->connection->executeStatement($sql, $params);
    }

    public function insert(string $table, array $data): int
    {
        return $this->connection->insert($table, $data);
    }

    public function update(string $table, array $data, array $criteria): int
    {
        return $this->connection->update($table, $data, $criteria);
    }

    public function delete(string $table, array $criteria): int
    {
        return $this->connection->delete($table, $criteria);
    }
}
