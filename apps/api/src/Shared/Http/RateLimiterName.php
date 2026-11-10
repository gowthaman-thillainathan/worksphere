<?php

declare(strict_types=1);

namespace Meridian\Shared\Http;

/**
 * Named rate limiters shared by all modules.
 */
enum RateLimiterName: string
{
    /** Default budget for every API request, keyed by user or IP. */
    case Api = 'api';
}
