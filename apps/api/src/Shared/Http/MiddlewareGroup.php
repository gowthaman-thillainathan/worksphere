<?php

declare(strict_types=1);

namespace Meridian\Shared\Http;

/**
 * Route middleware groups shared across modules. Their contents are defined
 * by the module that owns the concern (Identity defines what "authenticated"
 * means), so other modules depend on the name only.
 */
enum MiddlewareGroup: string
{
    /** A signed-in user with a verified e-mail address. */
    case Authenticated = 'authenticated';
}
