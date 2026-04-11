<?php

namespace App\Core;

use PDO;
use PDOStatement;

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct(string $path)
    {
        $this->pdo = new PDO("sqlite:$path");
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
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
        $db->pdo->exec($schema);

        // Migrations
        $columns = array_column(
            $db->pdo->query('PRAGMA table_info(users)')->fetchAll(\PDO::FETCH_ASSOC),
            'name'
        );
        if (!in_array('avatar', $columns)) {
            $db->pdo->exec('ALTER TABLE users ADD COLUMN avatar TEXT');
        }
    }

    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function lastInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }
}
