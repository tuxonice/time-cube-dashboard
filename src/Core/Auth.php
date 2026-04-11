<?php

namespace App\Core;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class Auth
{
    private static ?SessionInterface $session = null;

    public static function setSession(SessionInterface $session): void
    {
        self::$session = $session;
    }

    private static function session(): SessionInterface
    {
        if (self::$session === null) {
            throw new \RuntimeException('Session not initialized. Call Auth::setSession() first.');
        }
        return self::$session;
    }

    public static function login(array $user): void
    {
        self::session()->set('user_id', $user['id']);
        self::session()->set('username', $user['username']);
        self::session()->set('avatar', $user['avatar'] ?? null);
    }

    public static function logout(): void
    {
        self::session()->invalidate();
    }

    public static function check(): bool
    {
        return self::session()->has('user_id');
    }

    public static function userId(): ?int
    {
        return self::session()->get('user_id');
    }

    public static function username(): ?string
    {
        return self::session()->get('username');
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        return [
            'id'       => self::userId(),
            'username' => self::username(),
            'avatar'   => self::session()->get('avatar'),
        ];
    }
}
