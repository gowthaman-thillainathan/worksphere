<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Infrastructure\Audit;

use Meridian\Modules\Audit\Contracts\ActorDirectory;
use Meridian\Modules\Identity\Domain\Repositories\UserRepository;

/**
 * Audit actors are users: their names come from the accounts.
 */
final readonly class UserActorDirectory implements ActorDirectory
{
    public function __construct(private UserRepository $users) {}

    public function namesOf(array $actorIds): array
    {
        return $this->users->namesByIds($actorIds);
    }
}
