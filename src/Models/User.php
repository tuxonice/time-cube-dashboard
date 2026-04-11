<?php

namespace App\Models;

use App\Core\Database;

class User
{
    public static function find(int $id): ?array
    {
        return Database::getInstance()->fetch('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public static function findByEmail(string $email): ?array
    {
        $result = Database::getInstance()->fetch(
            'SELECT * FROM users WHERE email = ?',
            [$email]
        );
        return $result ?: null;
    }

    public static function create(string $email, string $name, string $password): int
    {
        Database::getInstance()->insert('users', [
            'email' => $email,
            'name' => $name,
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ]);
        return Database::getInstance()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        // Only allow updating specific fields
        $allowedFields = ['name', 'email', 'password', 'avatar'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));
        
        if (!empty($updateData)) {
            Database::getInstance()->update('users', $updateData, ['id' => $id]);
        }
    }

    public static function updateAvatar(int $id, ?string $avatar): void
    {
        Database::getInstance()->update('users', ['avatar' => $avatar], ['id' => $id]);
    }
}
