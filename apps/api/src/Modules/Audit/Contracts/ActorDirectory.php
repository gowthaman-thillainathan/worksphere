<?php

declare(strict_types=1);

namespace Meridian\Modules\Audit\Contracts;

/**
 * Display names for audit actors. Audit owns the interface and the module
 * that owns accounts (Identity) implements it, so Audit stays independent of
 * business modules. Without an implementation, names are simply omitted.
 */
interface ActorDirectory
{
    /**
     * @param  list<string>  $actorIds
     * @return array<string, string> actor id => display name, for the ids that are known
     */
    public function namesOf(array $actorIds): array;
}
