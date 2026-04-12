<?php

namespace App\Core;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Result;
use Doctrine\Migrations\Configuration\Migration\PhpFile;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Version\Direction;

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
            $dbPath = dirname(__DIR__, 2) . '/storage/database/app.db';
            self::$instance = new self($dbPath);
        }
        return self::$instance;
    }

    public static function init(): void
    {
        $db = self::getInstance();

        // Run migrations automatically if needed
        try {
            $config = new PhpFile(dirname(__DIR__, 2) . '/migrations-config.php');
            $dependencyFactory = DependencyFactory::fromConnection(
                $config,
                new ExistingConnection($db->connection)
            );

            $statusCalculator = $dependencyFactory->getMigrationStatusCalculator();
            $migrator = $dependencyFactory->getMigrator();

            // Check if there are new migrations to execute
            $newMigrations = $statusCalculator->getNewMigrations();

            if (count($newMigrations) > 0) {
                // Execute all new migrations
                $planCalculator = $dependencyFactory->getMigrationPlanCalculator();
                $versions = $newMigrations->getItems();
                $plan = $planCalculator->getPlanForVersions($versions, Direction::UP);
                $migrator->migrate($plan);
            }
        } catch (\Exception $e) {
            // Silently continue if migrations fail (e.g., already executed)
            // In production, you might want to log this
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
