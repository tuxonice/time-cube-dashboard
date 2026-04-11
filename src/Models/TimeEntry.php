<?php

namespace App\Models;

use App\Core\Database;

class TimeEntry
{
    public static function find(int $id): ?array
    {
        return Database::getInstance()->fetch(
            'SELECT te.*, t.project_id, t.name as task_name, p.user_id, p.name as project_name
             FROM time_entries te
             JOIN tasks t ON te.task_id = t.id
             JOIN projects p ON t.project_id = p.id
             WHERE te.id = ?',
            [$id]
        );
    }

    public static function allForTask(int $taskId): array
    {
        return Database::getInstance()->fetchAll(
            'SELECT * FROM time_entries WHERE task_id = ? ORDER BY created_at DESC',
            [$taskId]
        );
    }

    public static function runningForTask(int $taskId): ?array
    {
        return Database::getInstance()->fetch(
            'SELECT * FROM time_entries WHERE task_id = ? AND started_at IS NOT NULL AND stopped_at IS NULL',
            [$taskId]
        );
    }

    public static function runningForUser(int $userId): ?array
    {
        return Database::getInstance()->fetch(
            'SELECT te.*, t.name as task_name, t.project_id, p.name as project_name
             FROM time_entries te
             JOIN tasks t ON te.task_id = t.id
             JOIN projects p ON t.project_id = p.id
             WHERE te.user_id = ? AND te.started_at IS NOT NULL AND te.stopped_at IS NULL',
            [$userId]
        );
    }

    public static function start(int $taskId, int $userId): int
    {
        Database::getInstance()->executeStatement(
            'INSERT INTO time_entries (task_id, user_id, started_at) VALUES (?, ?, datetime(\'now\'))',
            [$taskId, $userId]
        );
        return Database::getInstance()->lastInsertId();
    }

    public static function stop(int $id): void
    {
        Database::getInstance()->executeStatement(
            'UPDATE time_entries SET stopped_at = datetime(\'now\'),
             duration = CAST((julianday(datetime(\'now\')) - julianday(started_at)) * 86400 AS INTEGER)
             WHERE id = ?',
            [$id]
        );
    }

    public static function createManual(int $taskId, int $userId, int $duration, ?string $description): int
    {
        Database::getInstance()->insert('time_entries', [
            'task_id' => $taskId,
            'user_id' => $userId,
            'duration' => $duration,
            'description' => $description
        ]);
        return Database::getInstance()->lastInsertId();
    }

    public static function delete(int $id): void
    {
        Database::getInstance()->delete('time_entries', ['id' => $id]);
    }

    public static function totalForTask(int $taskId): int
    {
        $row = Database::getInstance()->fetch(
            'SELECT COALESCE(SUM(duration), 0) as total FROM time_entries WHERE task_id = ?',
            [$taskId]
        );
        return (int) $row['total'];
    }

    public static function todayTotalForUser(int $userId): int
    {
        $row = Database::getInstance()->fetch(
            'SELECT COALESCE(SUM(duration), 0) as total FROM time_entries
             WHERE user_id = ? AND date(created_at) = date(\'now\')',
            [$userId]
        );
        return (int) $row['total'];
    }
}
