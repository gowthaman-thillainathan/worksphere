<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Application\Data;

use Meridian\Modules\Identity\Infrastructure\Persistence\Models\User;

final readonly class AuthenticatedSession
{
    public function __construct(
        public User $user,
        public TokenPair $tokens,
    ) {}
}
