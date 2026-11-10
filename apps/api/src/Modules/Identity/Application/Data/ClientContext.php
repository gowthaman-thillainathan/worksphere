<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Application\Data;

/**
 * Where a request came from. Stored with refresh tokens so users (and
 * investigators) can recognise their sessions.
 */
final readonly class ClientContext
{
    private const MAX_USER_AGENT_LENGTH = 512;

    public ?string $userAgent;

    public function __construct(
        public ?string $ipAddress,
        ?string $userAgent,
    ) {
        $this->userAgent = $userAgent === null ? null : mb_substr($userAgent, 0, self::MAX_USER_AGENT_LENGTH);
    }
}
