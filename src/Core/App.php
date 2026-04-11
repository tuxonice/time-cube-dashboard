<?php

namespace App\Core;

class App
{
    private Router $router;

    public function __construct()
    {
        session_start();

        Database::init();

        $this->router = new Router();
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
