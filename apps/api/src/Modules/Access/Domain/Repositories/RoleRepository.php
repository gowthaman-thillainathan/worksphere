<?php

declare(strict_types=1);

namespace Meridian\Modules\Access\Domain\Repositories;

use Meridian\Modules\Access\Infrastructure\Persistence\Models\Role;

interface RoleRepository
{
    public function findByKey(string $key): ?Role;

    /**
     * @return list<Role> Roles with their permissions loaded.
     */
    public function allWithPermissions(): array;

    /**
     * Creates or updates a system role and replaces its permission set.
     *
     * @param  list<string>  $permissionIds
     */
    public function saveSystemRole(string $key, string $name, string $description, array $permissionIds): Role;

    /**
     * Idempotent: assigning a role the user already holds is a no-op.
     */
    public function assign(string $userId, Role $role, ?string $grantedBy): void;

    /**
     * Removes every assignment of the user other than the given role.
     */
    public function revokeAllExcept(string $userId, Role $keep): void;

    /**
     * @return list<string> Keys of roles actively assigned to the user.
     */
    public function keysAssignedTo(string $userId): array;
}
