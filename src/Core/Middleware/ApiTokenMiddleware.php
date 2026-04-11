<?php

namespace App\Core\Middleware;

use App\Models\ApiToken;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): ?Response
    {
        $token = $request->headers->get('X-Time-Cube-Token', '');

        if ($token === '') {
            return new JsonResponse(['error' => 'Missing authorization token'], 401);
        }

        $apiToken = ApiToken::findByToken($token);
        if (!$apiToken) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
        }

        // Store token data in request attributes for use in controllers
        $request->attributes->set('api_token', $apiToken);
        $request->attributes->set('user_id', $apiToken['user_id']);

        return null; // Continue to next middleware
    }
}
