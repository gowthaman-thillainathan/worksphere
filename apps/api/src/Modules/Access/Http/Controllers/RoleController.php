<?php

declare(strict_types=1);

namespace Meridian\Modules\Access\Http\Controllers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Meridian\Modules\Access\Application\Queries\ListRoles;
use Meridian\Modules\Access\Http\Resources\RoleResource;

final readonly class RoleController
{
    public function __construct(private ListRoles $listRoles) {}

    /**
     * GET /roles — roles with their permissions (requires access.manage).
     */
    public function index(): AnonymousResourceCollection
    {
        return RoleResource::collection($this->listRoles->handle());
    }
}
