<?php

namespace App\Models;

use App\Core\Database;

class User
{
    public static function find(int $id): ?array
    {
        return Database::getInstance()->fetch('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public static function findByUsername(string $username): ?array
    {
        return Database::getInstance()->fetch('SELECT * FROM users WHERE username = ?', [$username]);
    }

    public static function create(string $username, string $password): int
    {
        Database::getInstance()->query(
            'INSERT INTO users (username, password) VALUES (?, ?)',
            [$username, password_hash($password, PASSWORD_DEFAULT)]
        );
        return Database::getInstance()->lastInsertId();
    }

    public static function updateUsername(int $id, string $username): void
    {
        Database::getInstance()->query(
            'UPDATE users SET username = ? WHERE id = ?',
            [$username, $id]
        );
    }

    public static function updatePassword(int $id, string $password): void
    {
        Database::getInstance()->query(
            'UPDATE users SET password = ? WHERE id = ?',
            [password_hash($password, PASSWORD_DEFAULT), $id]
        );
    }

    public static function updateAvatar(int $id, ?string $avatar): void
    {
        Database::getInstance()->query(
            'UPDATE users SET avatar = ? WHERE id = ?',
            [$avatar, $id]
        );
    }
}
