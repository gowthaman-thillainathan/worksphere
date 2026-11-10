<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Domain\Events;

/**
 * A sign-in attempt was rejected. userId is null when no account matched.
 */
final readonly class LoginFailed implements SecurityEvent
{
    public function __construct(
        public ?string $userId,
        public LoginFailureReason $reason,
        public ?string $ipAddress,
    ) {}

    public function name(): string
    {
        return 'identity.login_failed';
    }

    public function isSuspicious(): bool
    {
        return false;
    }

    public function context(): array
    {
        return [
            'user_id' => $this->userId,
            'reason' => $this->reason->value,
            'ip_address' => $this->ipAddress,
        ];
    }
}
