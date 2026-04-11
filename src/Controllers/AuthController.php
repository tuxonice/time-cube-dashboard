<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\User;

class AuthController extends Controller
{
    public function loginForm(): void
    {
        if (Auth::check()) {
            $this->redirect('/');
        }
        $this->render('auth/login.twig');
    }

    public function login(): void
    {
        $username = trim($this->post('username', ''));
        $password = $this->post('password', '');

        $user = User::findByUsername($username);

        if (!$user || !password_verify($password, $user['password'])) {
            $this->flash('error', 'Invalid username or password.');
            $this->redirect('/login');
        }

        Auth::login($user);
        $this->redirect('/');
    }

    public function registerForm(): void
    {
        if (Auth::check()) {
            $this->redirect('/');
        }
        $this->render('auth/register.twig');
    }

    public function register(): void
    {
        $username = trim($this->post('username', ''));
        $password = $this->post('password', '');
        $passwordConfirm = $this->post('password_confirm', '');

        if (strlen($username) < 3) {
            $this->flash('error', 'Username must be at least 3 characters.');
            $this->redirect('/register');
        }

        if (strlen($password) < 6) {
            $this->flash('error', 'Password must be at least 6 characters.');
            $this->redirect('/register');
        }

        if ($password !== $passwordConfirm) {
            $this->flash('error', 'Passwords do not match.');
            $this->redirect('/register');
        }

        if (User::findByUsername($username)) {
            $this->flash('error', 'Username already taken.');
            $this->redirect('/register');
        }

        $id = User::create($username, $password);
        Auth::login(['id' => $id, 'username' => $username]);
        $this->flash('success', 'Account created successfully.');
        $this->redirect('/');
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/login');
    }
}
