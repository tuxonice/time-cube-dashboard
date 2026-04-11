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
        self::session()->set('email', $user['email']);
        self::session()->set('name', $user['name']);
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

    public static function email(): ?string
    {
        return self::session()->get('email');
    }

    public static function name(): ?string
    {
        return self::session()->get('name');
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        return [
            'id'     => self::userId(),
            'email'  => self::email(),
            'name'   => self::name(),
            'avatar' => self::session()->get('avatar'),
        ];
    }
}
