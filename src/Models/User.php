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
        Database::getInstance()->insert('users', [
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ]);
        return Database::getInstance()->lastInsertId();
    }

    public static function updateUsername(int $id, string $username): void
    {
        Database::getInstance()->update('users', ['username' => $username], ['id' => $id]);
    }

    public static function updatePassword(int $id, string $password): void
    {
        Database::getInstance()->update('users', [
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ], ['id' => $id]);
    }

    public static function updateAvatar(int $id, ?string $avatar): void
    {
        Database::getInstance()->update('users', ['avatar' => $avatar], ['id' => $id]);
    }
}
