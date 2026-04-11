<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\ApiToken;

class SettingsController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $tokens = ApiToken::allForUser(Auth::userId());
        $this->render('settings/index.twig', ['tokens' => $tokens]);
    }

    public function createToken(): void
    {
        $this->requireAuth();
        $name = trim($this->post('name', ''));

        if ($name === '') {
            $this->flash('error', 'Token name is required.');
            $this->redirect('/settings');
        }

        $token = ApiToken::create(Auth::userId(), $name);
        $this->flash('success', "Token created: {$token} — copy it now, it won't be shown again.");
        $this->redirect('/settings');
    }

    public function deleteToken(string $id): void
    {
        $this->requireAuth();
        $token = ApiToken::find((int) $id);

        if (!$token || $token['user_id'] !== Auth::userId()) {
            $this->flash('error', 'Token not found.');
            $this->redirect('/settings');
        }

        ApiToken::delete((int) $id);
        $this->flash('success', 'Token deleted.');
        $this->redirect('/settings');
    }
}
