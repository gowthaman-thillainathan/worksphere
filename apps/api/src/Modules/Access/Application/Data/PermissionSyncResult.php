<?php

declare(strict_types=1);

namespace Meridian\Modules\Access\Application\Data;

final readonly class PermissionSyncResult
{
    public function __construct(
        public int $permissions,
        public int $removedPermissions,
        public int $systemRoles,
    ) {}
}
