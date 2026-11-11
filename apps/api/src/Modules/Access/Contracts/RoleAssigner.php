<?php

declare(strict_types=1);

namespace Meridian\Modules\Access\Contracts;

use Meridian\Modules\Access\Contracts\Exceptions\RoleNotFound;

/**
 * Grants roles to users. Exposed to other modules (e.g. Identity when an
 * account is provisioned).
 */
interface RoleAssigner
{
    /**
     * @throws RoleNotFound
     */
    public function assign(string $userId, string $roleKey, ?string $grantedBy = null): void;

    /**
     * Makes the role the user's only role.
     *
     * @throws RoleNotFound
     */
    public function replace(string $userId, string $roleKey, ?string $grantedBy = null): void;

    /**
     * @return list<string> Role keys currently assigned to the user.
     */
    public function rolesOf(string $userId): array;
}
