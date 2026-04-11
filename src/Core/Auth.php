<?php

namespace App\Core;

class Auth
{
    public static function login(array $user): void
    {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['avatar'] = $user['avatar'] ?? null;
    }

    public static function logout(): void
    {
        session_destroy();
        $_SESSION = [];
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function userId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function username(): ?string
    {
        return $_SESSION['username'] ?? null;
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        return [
            'id'       => self::userId(),
            'username' => self::username(),
            'avatar'   => $_SESSION['avatar'] ?? null,
        ];
    }
}
