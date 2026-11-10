<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Domain\Events;

/**
 * A rotated (already used) refresh token was presented again: the token was probably stolen, so the whole session family was revoked.
 */
final readonly class RefreshTokenReuseDetected implements SecurityEvent
{
    public function __construct(
        public string $userId,
        public string $sessionId,
        public ?string $ipAddress,
    ) {}

    public function name(): string
    {
        return 'identity.refresh_token_reuse_detected';
    }

    public function isSuspicious(): bool
    {
        return true;
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
