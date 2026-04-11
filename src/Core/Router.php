<?php

namespace App\Core;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class Router
{
    private array $routes = [];
    private SessionInterface $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function add(string $method, string $path, string $controller, string $action): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'controller' => $controller,
            'action' => $action,
        ];
    }

    public function get(string $path, string $controller, string $action): void
    {
        $this->add('GET', $path, $controller, $action);
    }

    public function post(string $path, string $controller, string $action): void
    {
        $this->add('POST', $path, $controller, $action);
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $uri = parse_url($uri, PHP_URL_PATH);
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
                    http_response_code(500);
                    echo "Controller {$controllerClass} not found";
                    return;
                }

                $controller = new $controllerClass($this->session);

                if (!method_exists($controller, $action)) {
                    http_response_code(500);
                    echo "Action {$action} not found in {$controllerClass}";
                    return;
                }

                $controller->$action(...array_values($params));
                return;
            }
        }

        http_response_code(404);
        echo "404 Not Found";
    }
}
