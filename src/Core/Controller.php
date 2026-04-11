<?php

namespace App\Core;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

abstract class Controller
{
    protected Environment $twig;
    protected SessionInterface $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;

        $loader = new FilesystemLoader(dirname(__DIR__, 2) . '/templates');
        $this->twig = new Environment($loader, [
            'cache' => false,
            'debug' => true,
        ]);

        $this->twig->addGlobal('auth', Auth::user());
        $this->twig->addGlobal('flash', $this->getFlash());
    }

    protected function render(string $template, array $data = []): void
    {
        echo $this->twig->render($template, $data);
    }

    protected function redirect(string $url): void
    {
        header("Location: $url");
        exit;
    }

    protected function requireAuth(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }
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
        return $_POST[$key] ?? $default;
    }

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function requireApiToken(): array
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = '';
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            $token = $matches[1];
        }

        if ($token === '') {
            $this->json(['error' => 'Missing authorization token'], 401);
        }

        $apiToken = \App\Models\ApiToken::findByToken($token);
        if (!$apiToken) {
            $this->json(['error' => 'Invalid token'], 401);
        }

        return $apiToken;
    }
}
