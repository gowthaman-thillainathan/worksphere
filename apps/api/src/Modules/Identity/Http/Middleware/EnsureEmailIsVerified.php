<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Meridian\Modules\Identity\Domain\Exceptions\EmailNotVerified;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks users who have not confirmed their e-mail address. Runs after
 * authentication, so a missing user is left to the auth middleware.
 */
final class EnsureEmailIsVerified
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            throw new EmailNotVerified;
        }

        return $next($request);
    }
}
