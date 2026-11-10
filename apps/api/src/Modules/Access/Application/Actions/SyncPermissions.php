<?php

declare(strict_types=1);

namespace Meridian\Modules\Access\Application\Actions;

use Illuminate\Database\ConnectionInterface;
use Meridian\Modules\Access\Application\Data\PermissionSyncResult;
use Meridian\Modules\Access\Contracts\Permission;
use Meridian\Modules\Access\Domain\Repositories\PermissionRepository;
use Meridian\Modules\Access\Domain\Repositories\RoleRepository;
use Meridian\Modules\Access\Domain\ValueObjects\SystemRole;

/**
 * Mirrors the permission catalogue and system role templates from code into
 * the database. Idempotent; runs on every deploy (docs/08 §5).
 */
final readonly class SyncPermissions
{
    public function __construct(
        private PermissionRepository $permissions,
        private RoleRepository $roles,
        private ConnectionInterface $database,
    ) {}

    public function handle(): PermissionSyncResult
    {
        return $this->database->transaction(function (): PermissionSyncResult {
            $catalogue = Permission::cases();
            $removed = $this->permissions->syncCatalogue($catalogue);

            foreach (SystemRole::cases() as $systemRole) {
                $this->roles->saveSystemRole(
                    key: $systemRole->value,
                    name: $systemRole->displayName(),
                    description: $systemRole->description(),
                    permissionIds: $this->permissions->idsOf($systemRole->permissions()),
                );
            }

            return new PermissionSyncResult(
                permissions: count($catalogue),
                removedPermissions: $removed,
                systemRoles: count(SystemRole::cases()),
            );
        });
    }
}
