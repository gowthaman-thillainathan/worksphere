<?php

declare(strict_types=1);

namespace Meridian\Modules\Organisation\Infrastructure\Persistence\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Meridian\Modules\Organisation\Contracts\DepartmentDirectory;
use Meridian\Modules\Organisation\Domain\Repositories\DepartmentRepository;
use Meridian\Modules\Organisation\Infrastructure\Persistence\Models\Department;

final class EloquentDepartmentRepository implements DepartmentDirectory, DepartmentRepository
{
    private const PARENT_COLUMNS = 'parent:id,code,name';

    public function find(string $id): ?Department
    {
        return Department::query()->with(self::PARENT_COLUMNS)->find($id);
    }

    public function findForUpdate(string $id): ?Department
    {
        return Department::query()->lockForUpdate()->find($id);
    }

    public function all(): array
    {
        return Department::query()->with(self::PARENT_COLUMNS)->orderBy('name')->get()->values()->all();
    }

    public function save(Department $department): void
    {
        $department->save();
    }

    public function delete(Department $department): void
    {
        $department->delete();
    }

    public function codeInUse(string $code, ?string $exceptId = null): bool
    {
        return Department::query()
            ->where('code', mb_strtoupper(trim($code)))
            ->when($exceptId, static fn (Builder $query, string $id) => $query->whereKeyNot($id))
            ->exists();
    }

    public function parentIdOf(string $id): ?string
    {
        /** @var string|null */
        return Department::query()->whereKey($id)->value('parent_id');
    }

    public function hasChildren(string $id): bool
    {
        return Department::query()->where('parent_id', $id)->exists();
    }

    public function exists(string $departmentId): bool
    {
        return Department::query()->whereKey($departmentId)->exists();
    }
}
