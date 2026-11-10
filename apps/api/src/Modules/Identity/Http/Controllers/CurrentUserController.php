<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Http\Controllers;

use Illuminate\Http\Request;
use Meridian\Modules\Access\Contracts\Authorizer;
use Meridian\Modules\Access\Contracts\Permission;
use Meridian\Modules\Access\Contracts\RoleAssigner;
use Meridian\Modules\Identity\Http\Resources\UserResource;
use Meridian\Modules\Identity\Infrastructure\Persistence\Models\User;

final readonly class CurrentUserController
{
    public function __construct(
        private Authorizer $authorizer,
        private RoleAssigner $roles,
    ) {}

    /**
     * GET /me — the signed-in user with their roles and effective permissions,
     * so the SPA can tailor navigation (the API still enforces every rule).
     */
    public function show(Request $request): UserResource
    {
        /** @var User $user */
        $user = $request->user();

        return (new UserResource($user))->additional([
            'meta' => [
                'roles' => $this->roles->rolesOf($user->id),
                'permissions' => array_map(
                    static fn (Permission $permission): string => $permission->value,
                    $this->authorizer->permissionsFor($user->id),
                ),
            ],
        ]);
    }
}
