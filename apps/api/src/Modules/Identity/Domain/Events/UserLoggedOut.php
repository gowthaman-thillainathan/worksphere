<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Domain\Events;

/**
 * A session (or, when everywhere is true, every session) was ended.
 */
final readonly class UserLoggedOut implements SecurityEvent
{
    public function __construct(
        public string $userId,
        public string $sessionId,
        public bool $everywhere,
    ) {}

    public function name(): string
    {
        return 'identity.logged_out';
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
            'everywhere' => $this->everywhere,
        ];
    }
}
