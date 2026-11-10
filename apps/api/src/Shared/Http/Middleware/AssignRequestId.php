<?php

declare(strict_types=1);

namespace Meridian\Shared\Http\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Meridian\Shared\Infrastructure\Logging\ContextKey;
use Symfony\Component\HttpFoundation\Response;

/**
 * Accepts a caller-supplied request id (when it is a valid UUID) or creates
 * one, shares it with logs and jobs, and echoes it on the response.
 */
final readonly class AssignRequestId
{
    public function __construct(private Config $config) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) $this->config->get('api.request_id_header');
        $incoming = (string) $request->headers->get($header, '');
        $requestId = Str::isUuid($incoming) ? $incoming : (string) Str::uuid7();

        Context::add(ContextKey::RequestId->value, $requestId);

        $response = $next($request);
        $response->headers->set($header, $requestId);

        return $response;
    }
}
