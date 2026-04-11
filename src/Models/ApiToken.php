<?php

namespace App\Models;

use App\Core\Database;

class ApiToken
{
    public static function findByToken(string $token): ?array
    {
        return Database::getInstance()->fetch(
            'SELECT * FROM api_tokens WHERE token = ?',
            [$token]
        );
    }

    public static function allForUser(int $userId): array
    {
        return Database::getInstance()->fetchAll(
            'SELECT * FROM api_tokens WHERE user_id = ? ORDER BY created_at DESC',
            [$userId]
        );
    }

    public static function create(int $userId, string $name): string
    {
        $token = bin2hex(random_bytes(32));
        Database::getInstance()->query(
            'INSERT INTO api_tokens (user_id, token, name) VALUES (?, ?, ?)',
            [$userId, $token, $name]
        );
        return $token;
    }

    public static function delete(int $id): void
    {
        Database::getInstance()->query('DELETE FROM api_tokens WHERE id = ?', [$id]);
    }

    public static function find(int $id): ?array
    {
        return Database::getInstance()->fetch('SELECT * FROM api_tokens WHERE id = ?', [$id]);
    }
}
