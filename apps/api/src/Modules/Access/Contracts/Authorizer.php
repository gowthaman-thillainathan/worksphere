<?php

declare(strict_types=1);

namespace Meridian\Modules\Access\Contracts;

/**
 * The single decision point for "may this user do X?" (docs/07 §7).
 *
 * Gates, policies and query scopes delegate here so every rule is written once.
 */
interface Authorizer
{
    public function allows(string $userId, Permission $permission): bool;

    /**
     * @return list<Permission>
     */
    public function permissionsFor(string $userId): array;

    /**
     * Drops any cached decision data for the user.
     */
    public function forget(string $userId): void;
}
