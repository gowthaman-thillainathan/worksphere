<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Domain\Events;

/**
 * A user confirmed ownership of their e-mail address.
 */
final readonly class EmailWasVerified implements SecurityEvent
{
    public function __construct(
        public string $userId,
    ) {}

    public function name(): string
    {
        return 'identity.email_verified';
    }

    public function isSuspicious(): bool
    {
        return false;
    }

    public function context(): array
    {
        return [
            'user_id' => $this->userId,
        ];
    }
}
