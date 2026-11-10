<?php

declare(strict_types=1);

namespace Meridian\Modules\Access\Application\Actions;

use Meridian\Modules\Access\Contracts\Authorizer;
use Meridian\Modules\Access\Contracts\Exceptions\RoleNotFound;
use Meridian\Modules\Access\Contracts\RoleAssigner;
use Meridian\Modules\Access\Domain\Repositories\RoleRepository;

/**
 * Grants a role to a user and invalidates their cached permission set.
 */
final readonly class AssignRole implements RoleAssigner
{
    public function __construct(
        private RoleRepository $roles,
        private Authorizer $authorizer,
    ) {}

    public function assign(string $userId, string $roleKey, ?string $grantedBy = null): void
    {
        $role = $this->roles->findByKey($roleKey) ?? throw RoleNotFound::withKey($roleKey);

        $this->roles->assign($userId, $role, $grantedBy);
        $this->authorizer->forget($userId);
    }

    public function rolesOf(string $userId): array
    {
        return $this->roles->keysAssignedTo($userId);
    }
}
