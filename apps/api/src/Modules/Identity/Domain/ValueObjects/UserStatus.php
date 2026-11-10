<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Domain\ValueObjects;

/**
 * Administrative account state. Temporary lockouts after failed logins are
 * time-based (users.locked_until) and do not change the status.
 */
enum UserStatus: string
{
    case Active = 'active';
    case Disabled = 'disabled';

    public function canAuthenticate(): bool
    {
        return $this === self::Active;
    }
}
