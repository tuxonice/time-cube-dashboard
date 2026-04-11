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

        $user = Auth::user();
        $section = $this->post('section', '');

        if ($section === 'username') {
            $username = trim($this->post('username', ''));

            if (strlen($username) < 3) {
                $this->flash('error', 'Username must be at least 3 characters.');
                return $this->redirect('/profile');
            }

            if ($username !== $user['username']) {
                if (User::findByUsername($username)) {
                    $this->flash('error', 'Username already taken.');
                    return $this->redirect('/profile');
                }
                User::updateUsername($user['id'], $username);
                $this->session->set('username', $username);
                $this->flash('success', 'Username updated.');
            }
        } elseif ($section === 'password') {
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

            User::updatePassword($user['id'], $new);
            $this->flash('success', 'Password updated.');
        }

        return $this->redirect('/profile');
    }

    public function uploadAvatar(): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
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
