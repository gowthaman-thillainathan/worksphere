<?php

declare(strict_types=1);

namespace Meridian\Modules\Access\Application\Queries;

use Meridian\Modules\Access\Domain\Repositories\RoleRepository;
use Meridian\Modules\Access\Infrastructure\Persistence\Models\Role;

final readonly class ListRoles
{
    public function __construct(private RoleRepository $roles) {}

    /**
     * @return list<Role>
     */
    public function handle(): array
    {
        return $this->roles->allWithPermissions();
    }
}
