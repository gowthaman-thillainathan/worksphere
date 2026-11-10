<?php

declare(strict_types=1);

namespace Meridian\Modules\Access\Http\Controllers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Meridian\Modules\Access\Application\Queries\ListPermissions;
use Meridian\Modules\Access\Http\Resources\PermissionResource;

final readonly class PermissionController
{
    public function __construct(private ListPermissions $listPermissions) {}

    /**
     * GET /permissions — the permission catalogue (requires access.manage).
     */
    public function index(): AnonymousResourceCollection
    {
        return PermissionResource::collection($this->listPermissions->handle());
    }
}
