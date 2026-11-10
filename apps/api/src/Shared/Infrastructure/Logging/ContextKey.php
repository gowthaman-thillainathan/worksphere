<?php

declare(strict_types=1);

namespace Meridian\Shared\Infrastructure\Logging;

/**
 * Keys shared through Laravel's Context. Context values are attached to every
 * log entry and propagated into queued jobs (docs/09 §1).
 */
enum ContextKey: string
{
    case RequestId = 'request_id';
    case UserId = 'user_id';
}
