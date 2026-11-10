<?php

declare(strict_types=1);

namespace Meridian\Modules\Access\Infrastructure\Persistence\Repositories;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Meridian\Modules\Access\Contracts\Permission;
use Meridian\Modules\Access\Domain\Repositories\PermissionRepository;
use Meridian\Modules\Access\Infrastructure\Persistence\Models\PermissionRecord;

final class EloquentPermissionRepository implements PermissionRepository
{
    public function syncCatalogue(array $catalogue): int
    {
        PermissionRecord::query()->upsert(
            array_map(static fn (Permission $permission): array => [
                'id' => (string) Str::uuid7(),
                'key' => $permission->value,
                'module' => $permission->module(),
                'description' => $permission->description(),
                'is_sensitive' => $permission->isSensitive(),
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ], $catalogue),
            uniqueBy: ['key'],
            update: ['module', 'description', 'is_sensitive', 'updated_at'],
        );

        return PermissionRecord::query()
            ->whereNotIn('key', array_map(static fn (Permission $permission): string => $permission->value, $catalogue))
            ->delete();
    }

    public function all(): array
    {
        return PermissionRecord::query()->orderBy('key')->get()->values()->all();
    }

    public function idsOf(array $permissions): array
    {
        /** @var list<string> */
        return PermissionRecord::query()
            ->whereIn('key', array_map(static fn (Permission $permission): string => $permission->value, $permissions))
            ->pluck('id')
            ->all();
    }

    public function keysGrantedTo(string $userId): array
    {
        /** @var list<string> */
        return PermissionRecord::query()
            ->whereHas('roles.assignments', static function (Builder $query) use ($userId): void {
                $query->where('user_id', $userId)->activeAt(CarbonImmutable::now());
            })
            ->orderBy('key')
            ->pluck('key')
            ->all();
    }
}
