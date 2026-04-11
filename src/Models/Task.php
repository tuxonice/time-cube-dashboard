<?php

namespace App\Models;

use App\Core\Database;

class Task
{
    public static function find(int $id): ?array
    {
        return Database::getInstance()->fetch('SELECT * FROM tasks WHERE id = ?', [$id]);
    }

    public static function findWithProject(int $id): ?array
    {
        return Database::getInstance()->fetch(
            'SELECT t.*, p.name as project_name, p.user_id
             FROM tasks t JOIN projects p ON t.project_id = p.id
             WHERE t.id = ?',
            [$id]
        );
    }

    public static function allForProject(int $projectId): array
    {
        return Database::getInstance()->fetchAll(
            'SELECT t.*,
                    COALESCE(SUM(te.duration), 0) as total_seconds,
                    (SELECT COUNT(*) FROM time_entries 
                     WHERE task_id = t.id 
                     AND stopped_at IS NULL 
                     AND started_at IS NOT NULL) as has_running
             FROM tasks t
             LEFT JOIN time_entries te ON te.task_id = t.id
             WHERE t.project_id = ?
             GROUP BY t.id
             ORDER BY t.created_at DESC',
            [$projectId]
        );
    }

    public static function create(int $projectId, string $name, ?string $description): int
    {
        Database::getInstance()->insert('tasks', [
            'project_id' => $projectId,
            'name' => $name,
            'description' => $description
        ]);
        return Database::getInstance()->lastInsertId();
    }

    public static function update(int $id, string $name, ?string $description): void
    {
        Database::getInstance()->update('tasks', [
            'name' => $name,
            'description' => $description
        ], ['id' => $id]);
    }

    public static function delete(int $id): void
    {
        Database::getInstance()->delete('tasks', ['id' => $id]);
    }
}
