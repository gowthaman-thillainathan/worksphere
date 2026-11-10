<?php

declare(strict_types=1);

namespace Meridian\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures API clients always receive JSON, including framework-generated
 * errors, regardless of the Accept header they sent.
 */
final class ForceJsonResponse
{
    private const JSON = 'application/json';

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', self::JSON);

        return $next($request);
    }
}
