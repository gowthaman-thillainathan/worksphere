<?php

declare(strict_types=1);

namespace Meridian\Shared\Http\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hardening headers (OWASP Secure Headers Project).
 *
 * Registered as global middleware so that every response carries them,
 * including errors raised before route middleware runs (404, 401...).
 * JSON API responses additionally get a deny-all CSP and are never cached.
 */
final readonly class SecurityHeaders
{
    /**
     * @var array<string, string>
     */
    private const COMMON_HEADERS = [
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'Referrer-Policy' => 'no-referrer',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
    ];

    /**
     * @var array<string, string>
     */
    private const API_HEADERS = [
        'Content-Security-Policy' => "default-src 'none'; frame-ancestors 'none'",
        'Cross-Origin-Resource-Policy' => 'same-site',
        'Cache-Control' => 'no-store, private',
    ];

    public function __construct(private Config $config) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = $this->isApiRequest($request) ? [...self::COMMON_HEADERS, ...self::API_HEADERS] : self::COMMON_HEADERS;

        foreach ($headers as $name => $value) {
            $response->headers->set($name, $value);
        }

        return $response;
    }

    private function isApiRequest(Request $request): bool
    {
        return $request->is(trim((string) $this->config->get('api.prefix'), '/').'/*');
    }
}
