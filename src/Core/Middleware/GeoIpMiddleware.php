<?php

namespace App\Core\Middleware;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\ItemInterface;

class GeoIpMiddleware implements MiddlewareInterface
{
    private const API_URL = 'http://ip-api.com/json/';
    private const CACHE_DURATION = 3600; // 1 hour

    private FilesystemAdapter $cache;

    public function __construct()
    {
        // Initialize Symfony Cache with filesystem adapter
        $this->cache = new FilesystemAdapter(
            namespace: 'geoip',
            defaultLifetime: self::CACHE_DURATION,
            directory: sys_get_temp_dir() . '/time-cube-cache'
        );
    }

    public function handle(Request $request, callable $next): ?Response
    {
        // Get allowed countries from environment variable
        $allowedCountries = $this->getAllowedCountries();

        // If no countries configured, allow all
        if (empty($allowedCountries)) {
            return null;
        }

        // Get client IP address
        $ip = $this->getClientIp($request);

        // Skip check for localhost/private IPs
        if ($this->isPrivateIp($ip)) {
            return null;
        }

        // Get country code from IP
        $countryCode = $this->getCountryCode($ip);

        // If we couldn't determine country, allow by default (fail open)
        if ($countryCode === null) {
            return null;
        }

        // Check if country is allowed
        if (!in_array($countryCode, $allowedCountries, true)) {
            // Determine response type based on request
            if ($this->isApiRequest($request)) {
                return new JsonResponse([
                    'error' => 'Access denied',
                    'message' => 'Your country is not allowed to access this service'
                ], 403);
            }

            return new Response(
                '<h1>Access Denied</h1><p>Your country is not allowed to access this service.</p>',
                403
            );
        }

        // Country is allowed, continue
        return null;
    }

    private function getAllowedCountries(): array
    {
        $countries = $_ENV['ALLOWED_COUNTRIES'] ?? '';

        if (empty($countries)) {
            return [];
        }

        // Parse comma-separated country codes (e.g., "US,GB,DE")
        return array_map('trim', array_map('strtoupper', explode(',', $countries)));
    }

    private function getClientIp(Request $request): string
    {
        // Check for IP in various headers (for proxies/load balancers)
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            $ip = $request->server->get($header);
            if ($ip) {
                // X-Forwarded-For can contain multiple IPs, get the first one
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }

        return $request->getClientIp() ?? '127.0.0.1';
    }

    private function isPrivateIp(string $ip): bool
    {
        // Check if IP is localhost or private network
        if ($ip === '127.0.0.1' || $ip === '::1' || $ip === 'localhost') {
            return true;
        }

        // Check private IP ranges
        $privateRanges = [
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
        ];

        foreach ($privateRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    private function ipInRange(string $ip, string $range): bool
    {
        list($subnet, $mask) = explode('/', $range);
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - (int)$mask);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }

    private function getCountryCode(string $ip): ?string
    {
        try {
            // Use Symfony Cache with automatic cache management
            return $this->cache->get(
                md5($ip),
                function (ItemInterface $item) use ($ip): ?string {
                    // Set cache expiration
                    $item->expiresAfter(self::CACHE_DURATION);

                    // Query IP-API
                    $url = self::API_URL . $ip . '?fields=status,countryCode';
                    $response = @file_get_contents($url, false, stream_context_create([
                        'http' => [
                            'timeout' => 2,
                            'ignore_errors' => true
                        ]
                    ]));

                    if ($response === false) {
                        return null;
                    }

                    $data = json_decode($response, true);

                    if (!$data || !isset($data['status']) || $data['status'] !== 'success') {
                        return null;
                    }

                    return $data['countryCode'] ?? null;
                }
            );
        } catch (\Exception $e) {
            // Fail open - allow access if we can't determine country
            return null;
        }
    }

    private function isApiRequest(Request $request): bool
    {
        // Check if request is to API endpoint or expects JSON
        return str_starts_with($request->getPathInfo(), '/api/') ||
               $request->headers->get('Accept') === 'application/json' ||
               $request->headers->has('X-Time-Cube-Token');
    }
}
