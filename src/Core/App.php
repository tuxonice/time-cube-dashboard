<?php

namespace App\Core;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

class App
{
    private Router $router;
    private Session $session;

    public function __construct()
    {
        $this->session = new Session(new NativeSessionStorage());
        $this->session->start();

        Auth::setSession($this->session);

        Database::init();

        $this->router = new Router($this->session);
        $this->loadRoutes();
    }

    private function loadRoutes(): void
    {
        $router = $this->router;
        require dirname(__DIR__, 2) . '/config/routes.php';
    }

    public function run(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];

        $this->router->dispatch($method, $uri);
    }
}
