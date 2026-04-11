<?php

namespace App\Core;

use App\Core\Middleware\MiddlewareStack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class Router
{
    private array $routes = [];
    private array $globalMiddleware = [];
    private SessionInterface $session;
    private Request $request;

    public function __construct(SessionInterface $session, Request $request)
    {
        $this->session = $session;
        $this->request = $request;
    }

    /**
     * Add middleware that runs on all routes
     */
    public function addGlobalMiddleware(string $middlewareClass): void
    {
        $this->globalMiddleware[] = $middlewareClass;
    }

    public function add(string $method, string $path, string $controller, string $action, array $middleware = []): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'controller' => $controller,
            'action' => $action,
            'middleware' => $middleware,
        ];
    }

    public function get(string $path, string $controller, string $action, array $middleware = []): void
    {
        $this->add('GET', $path, $controller, $action, $middleware);
    }

    public function post(string $path, string $controller, string $action, array $middleware = []): void
    {
        $this->add('POST', $path, $controller, $action, $middleware);
    }

    public function dispatch(): ?Response
    {
        $method = strtoupper($this->request->getMethod());
        $uri = $this->request->getPathInfo();
        $uri = rtrim($uri, '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                $controllerClass = "App\\Controllers\\" . $route['controller'];
                $action = $route['action'];

                if (!class_exists($controllerClass)) {
                    return new Response("Controller {$controllerClass} not found", 500);
                }

                $controller = new $controllerClass($this->session, $this->request);

                if (!method_exists($controller, $action)) {
                    return new Response("Action {$action} not found in {$controllerClass}", 500);
                }

                // Execute middleware stack (global + route-specific)
                $middlewareStack = new MiddlewareStack();

                // Add global middleware first
                foreach ($this->globalMiddleware as $middlewareClass) {
                    if (class_exists($middlewareClass)) {
                        $middlewareStack->add(new $middlewareClass());
                    }
                }

                // Then add route-specific middleware
                foreach ($route['middleware'] as $middlewareClass) {
                    if (class_exists($middlewareClass)) {
                        $middlewareStack->add(new $middlewareClass());
                    }
                }

                // Execute middleware and controller action
                return $middlewareStack->handle($this->request, function ($request) use ($controller, $action, $params) {
                    $response = $controller->$action(...array_values($params));
                    return $response instanceof Response ? $response : new Response('');
                });
            }
        }

        return new Response('404 Not Found', 404);
    }
}
