<?php

namespace App\Core;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

abstract class Controller
{
    protected Environment $twig;
    protected SessionInterface $session;
    protected Request $request;

    public function __construct(SessionInterface $session, Request $request)
    {
        $this->session = $session;
        $this->request = $request;

        $loader = new FilesystemLoader(dirname(__DIR__, 2) . '/templates');
        $this->twig = new Environment($loader, [
            'cache' => false,
            'debug' => true,
        ]);

        $this->twig->addGlobal('auth', Auth::user());
        $this->twig->addGlobal('flash', $this->getFlash());
        $this->twig->addGlobal('csrf_token', Auth::csrfToken());
    }

    protected function render(string $template, array $data = []): Response
    {
        $content = $this->twig->render($template, $data);
        return new Response($content);
    }

    protected function redirect(string $url): RedirectResponse
    {
        return new RedirectResponse($url);
    }

    protected function requireAuth(): ?RedirectResponse
    {
        if (!Auth::check()) {
            return $this->redirect('/login');
        }
        return null;
    }

    protected function flash(string $type, string $message): void
    {
        $this->session->set('flash', ['type' => $type, 'message' => $message]);
    }

    private function getFlash(): ?array
    {
        $flash = $this->session->get('flash');
        $this->session->remove('flash');
        return $flash;
    }

    protected function post(string $key, mixed $default = null): mixed
    {
        return $this->request->request->get($key, $default);
    }

    protected function validateCsrf(): bool
    {
        $token = $this->post('csrf_token');
        return Auth::validateCsrfToken($token);
    }

    protected function requireCsrf(): ?Response
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token. Please try again.');
            return $this->redirect($this->request->headers->get('referer') ?? '/');
        }
        return null;
    }

    protected function json(array $data, int $status = 200): JsonResponse
    {
        return new JsonResponse($data, $status);
    }

    protected function requireApiToken(): array|JsonResponse
    {
        $token = $this->request->headers->get('X-Time-Cube-Token', '');

        if ($token === '') {
            return $this->json(['error' => 'Missing authorization token'], 401);
        }

        $apiToken = \App\Models\ApiToken::findByToken($token);
        if (!$apiToken) {
            return $this->json(['error' => 'Invalid token'], 401);
        }

        return $apiToken;
    }
}
