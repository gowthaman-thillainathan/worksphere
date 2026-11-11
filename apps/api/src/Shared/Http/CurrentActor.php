<?php

declare(strict_types=1);

namespace Meridian\Shared\Http;

use Illuminate\Http\Request;

/**
 * The id of the authenticated user making the request, for "granted by" /
 * "invited by" style fields.
 */
final class CurrentActor
{
    public static function id(Request $request): ?string
    {
        $id = $request->user()?->getAuthIdentifier();

        return is_string($id) ? $id : null;
    }
}
