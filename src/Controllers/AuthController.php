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
        if ($response = $this->requireCsrf()) {
            return $response;
        }

        $email = $this->post('email');
        $password = $this->post('password');

        if (!$email || !$password) {
            $this->flash('error', 'Email and password are required');
            return $this->redirect('/login');
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'Invalid email format');
            return $this->redirect('/login');
        }

        $user = User::findByEmail($email);
        if (!$user || !password_verify($password, $user['password'])) {
            $this->flash('error', 'Invalid credentials');
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
        if ($response = $this->requireCsrf()) {
            return $response;
        }

        $email = trim($this->post('email', ''));
        $name = trim($this->post('name', ''));
        $password = $this->post('password', '');

        if (!$email || !$name || !$password) {
            $this->flash('error', 'Email, name, and password are required');
            return $this->redirect('/register');
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'Invalid email format');
            return $this->redirect('/register');
        }

        // Validate name length
        if (strlen($name) < 2) {
            $this->flash('error', 'Name must be at least 2 characters');
            return $this->redirect('/register');
        }

        // Validate password length
        if (strlen($password) < 6) {
            $this->flash('error', 'Password must be at least 6 characters');
            return $this->redirect('/register');
        }

        if (User::findByEmail($email)) {
            $this->flash('error', 'Email already exists');
            return $this->redirect('/register');
        }

        $userId = User::create($email, $name, $password);
        $user = User::find($userId);

        Auth::login($user);
        $this->flash('success', 'Account created successfully.');
        return $this->redirect('/');
    }

    public function logout(): Response
    {
        Auth::logout();
        return $this->redirect('/login');
    }
}
