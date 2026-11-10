<?php

declare(strict_types=1);

namespace Meridian\Modules\Access\Application\Queries;

use Meridian\Modules\Access\Domain\Repositories\PermissionRepository;
use Meridian\Modules\Access\Infrastructure\Persistence\Models\PermissionRecord;

final readonly class ListPermissions
{
    public function __construct(private PermissionRepository $permissions) {}

    /**
     * @return list<PermissionRecord>
     */
    public function handle(): array
    {
        return $this->permissions->all();
    }
}
