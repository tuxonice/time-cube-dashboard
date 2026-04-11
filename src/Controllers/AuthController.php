<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function loginForm(): Response
    {
        if (Auth::check()) {
            return $this->redirect('/');
        }
        return $this->render('auth/login.twig');
    }

    public function login(): Response
    {
        $username = trim($this->post('username', ''));
        $password = $this->post('password', '');

        $user = User::findByUsername($username);

        if (!$user || !password_verify($password, $user['password'])) {
            $this->flash('error', 'Invalid username or password.');
            return $this->redirect('/login');
        }

        Auth::login($user);
        return $this->redirect('/');
    }

    public function registerForm(): Response
    {
        if (Auth::check()) {
            return $this->redirect('/');
        }
        return $this->render('auth/register.twig');
    }

    public function register(): Response
    {
        $username = trim($this->post('username', ''));
        $password = $this->post('password', '');
        $passwordConfirm = $this->post('password_confirm', '');

        if (strlen($username) < 3) {
            $this->flash('error', 'Username must be at least 3 characters.');
            return $this->redirect('/register');
        }

        if (strlen($password) < 6) {
            $this->flash('error', 'Password must be at least 6 characters.');
            return $this->redirect('/register');
        }

        if ($password !== $passwordConfirm) {
            $this->flash('error', 'Passwords do not match.');
            return $this->redirect('/register');
        }

        if (User::findByUsername($username)) {
            $this->flash('error', 'Username already taken.');
            return $this->redirect('/register');
        }

        $id = User::create($username, $password);
        Auth::login(['id' => $id, 'username' => $username]);
        $this->flash('success', 'Account created successfully.');
        return $this->redirect('/');
    }

    public function logout(): Response
    {
        Auth::logout();
        return $this->redirect('/login');
    }
}
