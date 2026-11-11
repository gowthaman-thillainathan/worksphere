<?php

declare(strict_types=1);

namespace Meridian\Modules\Audit\Infrastructure\Actors;

use Meridian\Modules\Audit\Contracts\ActorDirectory;

/**
 * Default when no module provides actor names.
 */
final class AnonymousActorDirectory implements ActorDirectory
{
    public function namesOf(array $actorIds): array
    {
        return [];
    }
}
