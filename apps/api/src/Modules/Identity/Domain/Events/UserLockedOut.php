<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Domain\Events;

use DateTimeImmutable;

/**
 * Too many failed sign-ins: the account is locked until lockedUntil.
 */
final readonly class UserLockedOut implements SecurityEvent
{
    public function __construct(
        public string $userId,
        public DateTimeImmutable $lockedUntil,
        public ?string $ipAddress,
    ) {}

    public function name(): string
    {
        return 'identity.account_locked';
    }

    public function isSuspicious(): bool
    {
        return true;
    }

    public function context(): array
    {
        return [
            'user_id' => $this->userId,
            'locked_until' => $this->lockedUntil->format(DATE_ATOM),
            'ip_address' => $this->ipAddress,
        ];
    }
}
