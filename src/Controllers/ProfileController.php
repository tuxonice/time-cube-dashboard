<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class ProfileController extends Controller
{
    private const UPLOAD_DIR = __DIR__ . '/../../public/uploads/avatars/';
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const MAX_SIZE = 2 * 1024 * 1024; // 2 MB

    public function show(): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }
        return $this->render('profile/show.twig');
    }

    public function update(): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        if ($response = $this->requireCsrf()) {
            return $response;
        }

        $user = Auth::user();
        $section = $this->post('section', '');

        if ($section === 'password') {
            $current = $this->post('current_password', '');
            $new     = $this->post('new_password', '');
            $confirm = $this->post('new_password_confirm', '');

            $fresh = User::find($user['id']);
            if (!password_verify($current, $fresh['password'])) {
                $this->flash('error', 'Current password is incorrect.');
                return $this->redirect('/profile');
            }

            if (strlen($new) < 6) {
                $this->flash('error', 'New password must be at least 6 characters.');
                return $this->redirect('/profile');
            }

            if ($new !== $confirm) {
                $this->flash('error', 'Passwords do not match.');
                return $this->redirect('/profile');
            }

            User::update($user['id'], ['password' => password_hash($new, PASSWORD_DEFAULT)]);
            $this->flash('success', 'Password updated.');
        } elseif ($section === 'profile') {
            $name = $this->post('name');
            $email = $this->post('email');
            $password = $this->post('password');

            $updateData = [];

            // Update name if provided
            if ($name && $name !== $user['name']) {
                if (strlen($name) < 2) {
                    $this->flash('error', 'Name must be at least 2 characters');
                    return $this->redirect('/profile');
                }
                $updateData['name'] = $name;
            }

            // Update email if provided and changed
            if ($email && $email !== $user['email']) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->flash('error', 'Invalid email format');
                    return $this->redirect('/profile');
                }

                // Check if email already exists
                $existingUser = User::findByEmail($email);
                if ($existingUser && $existingUser['id'] !== $user['id']) {
                    $this->flash('error', 'Email already in use');
                    return $this->redirect('/profile');
                }

                $updateData['email'] = $email;
            }

            // Update password if provided
            if ($password) {
                if (strlen($password) < 6) {
                    $this->flash('error', 'Password must be at least 6 characters');
                    return $this->redirect('/profile');
                }
                $updateData['password'] = password_hash($password, PASSWORD_DEFAULT);
            }

            if (!empty($updateData)) {
                User::update($user['id'], $updateData);
                $this->flash('success', 'Profile updated successfully');
                
                // Refresh user data in session
                $updatedUser = User::find($user['id']);
                Auth::login($updatedUser);
            }
        }

        return $this->redirect('/profile');
    }

    public function uploadAvatar(): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        if ($response = $this->requireCsrf()) {
            return $response;
        }

        $user = Auth::user();
        $file = $this->request->files->get('avatar');

        if (!$file || $file->getError() !== UPLOAD_ERR_OK) {
            $this->flash('error', 'No file uploaded or upload error.');
            return $this->redirect('/profile');
        }

        if ($file->getSize() > self::MAX_SIZE) {
            $this->flash('error', 'File exceeds the 2 MB size limit.');
            return $this->redirect('/profile');
        }

        $mime = $file->getMimeType();
        if (!in_array($mime, self::ALLOWED_TYPES, true)) {
            $this->flash('error', 'Only JPEG, PNG, GIF, and WebP images are allowed.');
            return $this->redirect('/profile');
        }

        $ext      = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        };
        $filename = $user['id'] . '.' . $ext;
        $dest     = self::UPLOAD_DIR . $filename;

        if (!is_dir(self::UPLOAD_DIR)) {
            mkdir(self::UPLOAD_DIR, 0755, true);
        }

        // Remove old avatar file if extension differs
        if ($user['avatar'] && $user['avatar'] !== $filename) {
            $old = self::UPLOAD_DIR . $user['avatar'];
            if (file_exists($old)) {
                unlink($old);
            }
        }

        try {
            $file->move(self::UPLOAD_DIR, $filename);
        } catch (\Exception $e) {
            $this->flash('error', 'Failed to save the image.');
            return $this->redirect('/profile');
        }

        User::updateAvatar($user['id'], $filename);
        $this->session->set('avatar', $filename);
        $this->flash('success', 'Avatar updated.');
        return $this->redirect('/profile');
    }

    public function removeAvatar(): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        if ($response = $this->requireCsrf()) {
            return $response;
        }

        $user = Auth::user();

        if ($user['avatar']) {
            $path = self::UPLOAD_DIR . $user['avatar'];
            if (file_exists($path)) {
                unlink($path);
            }
            User::updateAvatar($user['id'], null);
            $this->session->set('avatar', null);
        }

        $this->flash('success', 'Avatar removed.');
        return $this->redirect('/profile');
    }
}
