<?php

namespace App\Core\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface MiddlewareInterface
{
    /**
     * Handle the request and optionally pass to next middleware
     *
     * @param Request $request
     * @param callable $next
     * @return Response|null Returns Response to short-circuit, null to continue
     */
    public function handle(Request $request, callable $next): ?Response;
}
