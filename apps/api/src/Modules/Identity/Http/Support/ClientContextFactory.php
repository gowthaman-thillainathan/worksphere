<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Http\Support;

use Illuminate\Http\Request;
use Meridian\Modules\Identity\Application\Data\ClientContext;

final class ClientContextFactory
{
    public static function fromRequest(Request $request): ClientContext
    {
        return new ClientContext($request->ip(), $request->userAgent());
    }
}
