<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\User;

class ProfileController extends Controller
{
    private const UPLOAD_DIR = __DIR__ . '/../../public/uploads/avatars/';
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const MAX_SIZE = 2 * 1024 * 1024; // 2 MB

    public function show(): void
    {
        $this->requireAuth();
        $this->render('profile/show.twig');
    }

    public function update(): void
    {
        $this->requireAuth();

        $user = Auth::user();
        $section = $this->post('section', '');

        if ($section === 'username') {
            $username = trim($this->post('username', ''));

            if (strlen($username) < 3) {
                $this->flash('error', 'Username must be at least 3 characters.');
                $this->redirect('/profile');
            }

            if ($username !== $user['username']) {
                if (User::findByUsername($username)) {
                    $this->flash('error', 'Username already taken.');
                    $this->redirect('/profile');
                }
                User::updateUsername($user['id'], $username);
                $_SESSION['username'] = $username;
                $this->flash('success', 'Username updated.');
            }
        } elseif ($section === 'password') {
            $current = $this->post('current_password', '');
            $new     = $this->post('new_password', '');
            $confirm = $this->post('new_password_confirm', '');

            $fresh = User::find($user['id']);
            if (!password_verify($current, $fresh['password'])) {
                $this->flash('error', 'Current password is incorrect.');
                $this->redirect('/profile');
            }

            if (strlen($new) < 6) {
                $this->flash('error', 'New password must be at least 6 characters.');
                $this->redirect('/profile');
            }

            if ($new !== $confirm) {
                $this->flash('error', 'Passwords do not match.');
                $this->redirect('/profile');
            }

            User::updatePassword($user['id'], $new);
            $this->flash('success', 'Password updated.');
        }

        $this->redirect('/profile');
    }

    public function uploadAvatar(): void
    {
        $this->requireAuth();

        $user = Auth::user();
        $file = $_FILES['avatar'] ?? null;

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $this->flash('error', 'No file uploaded or upload error.');
            $this->redirect('/profile');
        }

        if ($file['size'] > self::MAX_SIZE) {
            $this->flash('error', 'File exceeds the 2 MB size limit.');
            $this->redirect('/profile');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        if (!in_array($mime, self::ALLOWED_TYPES, true)) {
            $this->flash('error', 'Only JPEG, PNG, GIF, and WebP images are allowed.');
            $this->redirect('/profile');
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

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            $this->flash('error', 'Failed to save the image.');
            $this->redirect('/profile');
        }

        User::updateAvatar($user['id'], $filename);
        $_SESSION['avatar'] = $filename;
        $this->flash('success', 'Avatar updated.');
        $this->redirect('/profile');
    }

    public function removeAvatar(): void
    {
        $this->requireAuth();

        $user = Auth::user();

        if ($user['avatar']) {
            $path = self::UPLOAD_DIR . $user['avatar'];
            if (file_exists($path)) {
                unlink($path);
            }
            User::updateAvatar($user['id'], null);
            $_SESSION['avatar'] = null;
        }

        $this->flash('success', 'Avatar removed.');
        $this->redirect('/profile');
    }
}