<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Domain\Events;

/**
 * A user signed in with valid credentials.
 */
final readonly class UserLoggedIn implements SecurityEvent
{
    public function __construct(
        public string $userId,
        public string $sessionId,
        public ?string $ipAddress,
    ) {}

    public function name(): string
    {
        return 'identity.login_succeeded';
    }

    public function isSuspicious(): bool
    {
        return false;
    }

    public function context(): array
    {
        return [
            'user_id' => $this->userId,
            'session_id' => $this->sessionId,
            'ip_address' => $this->ipAddress,
        ];
    }
}
