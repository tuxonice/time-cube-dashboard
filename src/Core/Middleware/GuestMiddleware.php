<?php

namespace App\Core\Middleware;

use App\Core\Auth;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GuestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): ?Response
    {
        if (Auth::check()) {
            return new RedirectResponse('/');
        }

        return null; // Continue to next middleware
    }
}
