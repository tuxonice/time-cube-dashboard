<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add avatar column to users table
 */
final class Version20260411000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add avatar column to users table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD COLUMN avatar TEXT');
    }

    public function down(Schema $schema): void
    {
        // SQLite doesn't support DROP COLUMN directly, would need table recreation
        $this->addSql('-- Cannot easily drop column in SQLite');
    }
}
