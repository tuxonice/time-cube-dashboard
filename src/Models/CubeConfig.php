<?php

namespace App\Models;

use App\Core\Database;

class CubeConfig
{
    // --- Cubes ---

    public static function findCube(int $id): ?array
    {
        return Database::getInstance()->fetch('SELECT * FROM cubes WHERE id = ?', [$id]);
    }

    public static function findCubeByIdentifier(string $cubeId): ?array
    {
        return Database::getInstance()->fetch('SELECT * FROM cubes WHERE cube_id = ?', [$cubeId]);
    }

    public static function allCubesForUser(int $userId): array
    {
        return Database::getInstance()->fetchAll(
            'SELECT c.*, (SELECT COUNT(*) FROM cube_face_mappings WHERE cube_id = c.id) as mapping_count
             FROM cubes c WHERE c.user_id = ? ORDER BY c.created_at DESC',
            [$userId]
        );
    }

    public static function createCube(int $userId, string $cubeId, string $name): int
    {
        Database::getInstance()->insert('cubes', [
            'user_id' => $userId,
            'cube_id' => $cubeId,
            'name' => $name
        ]);
        return Database::getInstance()->lastInsertId();
    }

    public static function deleteCube(int $id): void
    {
        Database::getInstance()->delete('cubes', ['id' => $id]);
    }

    // --- Face Mappings ---

    public static function mappingsForCube(int $cubeDbId): array
    {
        return Database::getInstance()->fetchAll(
            'SELECT cfm.*, t.name as task_name, p.name as project_name, p.id as project_id
             FROM cube_face_mappings cfm
             JOIN tasks t ON cfm.task_id = t.id
             JOIN projects p ON t.project_id = p.id
             WHERE cfm.cube_id = ?
             ORDER BY cfm.face_color',
            [$cubeDbId]
        );
    }

    public static function saveMapping(int $cubeDbId, string $faceColor, int $taskId): void
    {
        Database::getInstance()->executeStatement(
            'INSERT INTO cube_face_mappings (cube_id, face_color, task_id)
             VALUES (?, ?, ?)
             ON CONFLICT(cube_id, face_color)
             DO UPDATE SET task_id = excluded.task_id',
            [$cubeDbId, strtolower(trim($faceColor)), $taskId]
        );
    }

    public static function deleteMapping(int $id, int $cubeDbId): void
    {
        Database::getInstance()->delete('cube_face_mappings', ['id' => $id, 'cube_id' => $cubeDbId]);
    }

    public static function findTaskByFace(string $cubeIdentifier, string $faceColor): ?array
    {
        return Database::getInstance()->fetch(
            'SELECT cfm.task_id, t.name as task_name, t.project_id, p.name as project_name, c.user_id
             FROM cube_face_mappings cfm
             JOIN cubes c ON cfm.cube_id = c.id
             JOIN tasks t ON cfm.task_id = t.id
             JOIN projects p ON t.project_id = p.id
             WHERE c.cube_id = ? AND cfm.face_color = ?',
            [$cubeIdentifier, strtolower(trim($faceColor))]
        );
    }
}
