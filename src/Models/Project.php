<?php

namespace App\Models;

use App\Core\Database;

class Project
{
    public static function find(int $id): ?array
    {
        return Database::getInstance()->fetch('SELECT * FROM projects WHERE id = ?', [$id]);
    }

    public static function allForUser(int $userId): array
    {
        return Database::getInstance()->fetchAll(
            'SELECT p.*,
                    (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as task_count,
                    (SELECT COALESCE(SUM(te.duration), 0) FROM time_entries te
                     JOIN tasks t ON te.task_id = t.id WHERE t.project_id = p.id) as total_seconds
             FROM projects p WHERE p.user_id = ? ORDER BY p.created_at DESC',
            [$userId]
        );
    }

    public static function create(int $userId, string $name, ?string $description): int
    {
        Database::getInstance()->query(
            'INSERT INTO projects (user_id, name, description) VALUES (?, ?, ?)',
            [$userId, $name, $description]
        );
        return Database::getInstance()->lastInsertId();
    }

    public static function update(int $id, string $name, ?string $description): void
    {
        Database::getInstance()->query(
            'UPDATE projects SET name = ?, description = ? WHERE id = ?',
            [$name, $description, $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::getInstance()->query('DELETE FROM projects WHERE id = ?', [$id]);
    }
}
