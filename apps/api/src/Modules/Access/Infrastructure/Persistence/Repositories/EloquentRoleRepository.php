<?php

declare(strict_types=1);

namespace Meridian\Modules\Access\Infrastructure\Persistence\Repositories;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Meridian\Modules\Access\Domain\Repositories\RoleRepository;
use Meridian\Modules\Access\Infrastructure\Persistence\Models\Role;
use Meridian\Modules\Access\Infrastructure\Persistence\Models\RoleAssignment;

final class EloquentRoleRepository implements RoleRepository
{
    public function findByKey(string $key): ?Role
    {
        return Role::query()->where('key', $key)->first();
    }

    public function allWithPermissions(): array
    {
        return Role::query()->with('permissions')->orderBy('key')->get()->values()->all();
    }

    public function saveSystemRole(string $key, string $name, string $description, array $permissionIds): Role
    {
        $role = Role::query()->updateOrCreate(
            ['key' => $key],
            ['name' => $name, 'description' => $description, 'is_system' => true],
        );

        $role->permissions()->sync($permissionIds);

        return $role;
    }

    public function assign(string $userId, Role $role, ?string $grantedBy): void
    {
        RoleAssignment::query()->firstOrCreate(
            ['user_id' => $userId, 'role_id' => $role->id],
            ['granted_by' => $grantedBy],
        );
    }

    public function revokeAllExcept(string $userId, Role $keep): void
    {
        RoleAssignment::query()->where('user_id', $userId)->where('role_id', '!=', $keep->id)->delete();
    }

    public function keysAssignedTo(string $userId): array
    {
        /** @var list<string> */
        return Role::query()
            ->whereHas('assignments', static function (Builder $query) use ($userId): void {
                $query->where('user_id', $userId)->activeAt(CarbonImmutable::now());
            })
            ->orderBy('key')
            ->pluck('key')
            ->all();
    }
}
