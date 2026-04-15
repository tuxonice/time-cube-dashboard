<?php

declare(strict_types=1);

use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Configuration\Migration\PhpFile;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\DependencyFactory;

$config = new PhpFile(__DIR__ . '/migrations-config.php');

$dbPath = __DIR__ . '/../storage/database/app.db';
$connectionParams = [
    'driver' => 'pdo_sqlite',
    'path' => $dbPath,
];

$connection = DriverManager::getConnection($connectionParams);
$connection->executeStatement('PRAGMA foreign_keys = ON');

return DependencyFactory::fromConnection($config, new ExistingConnection($connection));
