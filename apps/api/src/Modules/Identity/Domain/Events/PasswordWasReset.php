<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Domain\Events;

/**
 * A password was changed through a reset link; all sessions were revoked.
 */
final readonly class PasswordWasReset implements SecurityEvent
{
    public function __construct(
        public string $userId,
    ) {}

    public function name(): string
    {
        return 'identity.password_reset';
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
