<?php

namespace App\Core;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

class App
{
    private Router $router;
    private Session $session;
    private Request $request;

    public function __construct()
    {
        $this->request = Request::createFromGlobals();
        
        $this->session = new Session(new NativeSessionStorage());
        $this->session->start();
        $this->request->setSession($this->session);

        Auth::setSession($this->session);

        Database::init();

        $this->router = new Router($this->session, $this->request);
        $this->loadRoutes();
    }

    private function loadRoutes(): void
    {
        $router = $this->router;
        require dirname(__DIR__, 2) . '/config/routes.php';
    }

    public function run(): void
    {
        $response = $this->router->dispatch();
        
        if ($response) {
            $response->send();
        }
    }
}
