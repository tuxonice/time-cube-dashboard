<?php

namespace App\Core\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MiddlewareStack
{
    private array $middleware = [];

    public function add(MiddlewareInterface $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Execute middleware stack and final handler
     *
     * @param Request $request
     * @param callable $handler Final handler to execute if all middleware pass
     * @return Response
     */
    public function handle(Request $request, callable $handler): Response
    {
        $next = $this->createNextClosure($this->middleware, $handler);
        return $next($request);
    }

    private function createNextClosure(array $middleware, callable $finalHandler): callable
    {
        if (empty($middleware)) {
            return $finalHandler;
        }

        $current = array_shift($middleware);
        $next = $this->createNextClosure($middleware, $finalHandler);

        return function (Request $request) use ($current, $next): Response {
            $result = $current->handle($request, $next);
            
            // If middleware returns a Response, use it (short-circuit)
            if ($result instanceof Response) {
                return $result;
            }
            
            // Otherwise continue to next middleware
            return $next($request);
        };
    }
}
