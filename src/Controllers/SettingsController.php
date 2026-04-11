<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\ApiToken;
use Symfony\Component\HttpFoundation\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }
        $tokens = ApiToken::allForUser(Auth::userId());
        return $this->render('settings/index.twig', ['tokens' => $tokens]);
    }

    public function createToken(): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        if ($response = $this->requireCsrf()) {
            return $response;
        }

        $name = trim($this->post('name', ''));

        if ($name === '') {
            $this->flash('error', 'Token name is required.');
            return $this->redirect('/settings');
        }

        $token = ApiToken::create(Auth::userId(), $name);
        $this->flash('success', "Token created: {$token} — copy it now, it won't be shown again.");
        return $this->redirect('/settings');
    }

    public function deleteToken(string $id): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        if ($response = $this->requireCsrf()) {
            return $response;
        }

        $token = ApiToken::find((int) $id);

        if (!$token || $token['user_id'] !== Auth::userId()) {
            $this->flash('error', 'Token not found.');
            return $this->redirect('/settings');
        }

        ApiToken::delete((int) $id);
        $this->flash('success', 'Token deleted.');
        return $this->redirect('/settings');
    }
}
