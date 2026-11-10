<?php

declare(strict_types=1);

namespace Meridian\Modules\Access\Domain\Repositories;

use Meridian\Modules\Access\Contracts\Permission;
use Meridian\Modules\Access\Infrastructure\Persistence\Models\PermissionRecord;

interface PermissionRepository
{
    /**
     * Mirrors the catalogue: inserts new permissions, updates changed ones and
     * removes permissions that no longer exist in code.
     *
     * @param  list<Permission>  $catalogue
     * @return int Number of permissions removed.
     */
    public function syncCatalogue(array $catalogue): int;

    /**
     * @return list<PermissionRecord>
     */
    public function all(): array;

    /**
     * @param  list<Permission>  $permissions
     * @return list<string> Database ids of the given permissions.
     */
    public function idsOf(array $permissions): array;

    /**
     * @return list<string> Permission keys granted by the user's active role assignments.
     */
    public function keysGrantedTo(string $userId): array;
}
