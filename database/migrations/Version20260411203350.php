<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260411203350 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add name field to users table and rename username column to email';
    }

    public function up(Schema $schema): void
    {
        // SQLite doesn't support ALTER COLUMN, so we need to recreate the table
        
        // Create new users table with email and name
        $this->connection->executeStatement('CREATE TABLE users_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL UNIQUE,
            name TEXT NOT NULL,
            password TEXT NOT NULL,
            avatar TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )');

        // Copy data from old table (username becomes email, name defaults to username)
        $this->connection->executeStatement('INSERT INTO users_new (id, email, name, password, avatar, created_at)
            SELECT id, username, username, password, avatar, created_at FROM users');

        // Drop old table
        $this->connection->executeStatement('DROP TABLE users');

        // Rename new table to users
        $this->connection->executeStatement('ALTER TABLE users_new RENAME TO users');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
