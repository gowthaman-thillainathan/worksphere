<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Infrastructure\Persistence\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Meridian\Modules\Employee\Contracts\EmployeeDirectory;
use Meridian\Modules\Employee\Domain\Repositories\EmployeeRepository;
use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeSearchCriteria;
use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeStatus;
use Meridian\Modules\Employee\Domain\ValueObjects\EmploymentType;
use Meridian\Modules\Employee\Domain\ValueObjects\TrashedFilter;
use Meridian\Modules\Employee\Infrastructure\Persistence\Models\Employee;
use Meridian\Modules\Employee\Infrastructure\Persistence\Models\EmployeeNumberSequence;
use Meridian\Shared\Domain\ValueObjects\CursorPage;

final class EloquentEmployeeRepository implements EmployeeDirectory, EmployeeRepository
{
    private const MANAGER_COLUMNS = 'manager:id,first_name,last_name,preferred_name';

    private const NAME_COLUMNS = ['first_name', 'last_name', 'preferred_name'];

    private const SEARCH_COLUMNS = [...self::NAME_COLUMNS, 'employee_number', 'work_email'];

    private const CASE_INSENSITIVE_LIKE = 'ilike';

    /**
     * Unique tie-breaker so cursor pagination is deterministic.
     */
    private const TIE_BREAKER = 'id';

    public function find(string $id): ?Employee
    {
        return Employee::query()->with(self::MANAGER_COLUMNS)->find($id);
    }

    public function findForUpdate(string $id, bool $includingTrashed = false): ?Employee
    {
        return Employee::query()
            ->when($includingTrashed, static fn (Builder $query) => $query->withTrashed())
            ->lockForUpdate()
            ->find($id);
    }

    public function search(EmployeeSearchCriteria $criteria): CursorPage
    {
        $query = Employee::query()->with(self::MANAGER_COLUMNS);

        $this->applyTrashed($query, $criteria->trashed);
        $this->applyFilters($query, $criteria);
        $this->applyContains($query, self::SEARCH_COLUMNS, $criteria->search);
        $this->applyContains($query, self::NAME_COLUMNS, $criteria->name);
        $this->applyContains($query, ['employee_number'], $criteria->employeeNumber);
        $this->applyContains($query, ['work_email'], $criteria->workEmail);
        $this->applyContains($query, ['job_title'], $criteria->jobTitle);

        foreach ($criteria->sort as $sort) {
            $query->orderBy($sort->field->column(), $sort->direction->value);
        }

        $paginator = $query->orderBy(self::TIE_BREAKER)->cursorPaginate(perPage: $criteria->perPage, cursor: $criteria->cursor);

        /** @var list<Employee> $items */
        $items = $paginator->items();

        return new CursorPage(
            items: $items,
            perPage: $paginator->perPage(),
            nextCursor: $paginator->nextCursor()?->encode(),
            previousCursor: $paginator->previousCursor()?->encode(),
        );
    }

    public function isActive(string $employeeId): bool
    {
        return Employee::query()->whereKey($employeeId)->exists();
    }

    public function save(Employee $employee): void
    {
        $employee->save();
    }

    public function softDelete(Employee $employee): void
    {
        // Eloquent's soft delete only writes deleted_at; persist the other changes first.
        $employee->save();
        $employee->delete();
    }

    public function restore(Employee $employee): void
    {
        $employee->restore();
    }

    public function workEmailInUse(string $workEmail, ?string $exceptEmployeeId = null): bool
    {
        return Employee::query()
            ->where('work_email', mb_strtolower($workEmail))
            ->when($exceptEmployeeId, static fn (Builder $query, string $id) => $query->whereKeyNot($id))
            ->exists();
    }

    public function managerIdOf(string $employeeId): ?string
    {
        /** @var string|null */
        return Employee::query()->whereKey($employeeId)->value('manager_id');
    }

    public function hasActiveDirectReports(string $employeeId): bool
    {
        return Employee::query()->where('manager_id', $employeeId)->exists();
    }

    public function countByStatus(): array
    {
        $counts = [];

        foreach (EmployeeStatus::cases() as $status) {
            $counts[$status->value] = Employee::query()->where('status', $status->value)->count();
        }

        return $counts;
    }

    public function nextSequenceValue(): int
    {
        $sequence = EmployeeNumberSequence::query()->lockForUpdate()->findOrFail(EmployeeNumberSequence::EMPLOYEES);
        $sequence->last_value++;
        $sequence->save();

        return $sequence->last_value;
    }

    public function renameDepartment(string $departmentId, string $name): int
    {
        return Employee::query()
            ->withTrashed()
            ->where('department_id', $departmentId)
            ->toBase()
            ->update(['department_name' => $name]);
    }

    /**
     * @param  Builder<Employee>  $query
     */
    private function applyTrashed(Builder $query, TrashedFilter $trashed): void
    {
        match ($trashed) {
            TrashedFilter::With => $query->withTrashed(),
            TrashedFilter::Only => $query->onlyTrashed(),
            TrashedFilter::Without => null,
        };
    }

    /**
     * @param  Builder<Employee>  $query
     */
    private function applyFilters(Builder $query, EmployeeSearchCriteria $criteria): void
    {
        $query
            ->when($criteria->statuses !== [], static fn (Builder $query) => $query->whereIn(
                'status',
                array_map(static fn (EmployeeStatus $status): string => $status->value, $criteria->statuses),
            ))
            ->when($criteria->employmentTypes !== [], static fn (Builder $query) => $query->whereIn(
                'employment_type',
                array_map(static fn (EmploymentType $type): string => $type->value, $criteria->employmentTypes),
            ))
            ->when($criteria->managerId, static fn (Builder $query, string $managerId) => $query->where('manager_id', $managerId))
            ->when($criteria->departmentId, static fn (Builder $query, string $departmentId) => $query->where('department_id', $departmentId))
            ->when($criteria->hiredFrom, static fn (Builder $query, $from) => $query->whereDate('hire_date', '>=', $from))
            ->when($criteria->hiredTo, static fn (Builder $query, $to) => $query->whereDate('hire_date', '<=', $to));
    }

    /**
     * Case-insensitive substring match on any of the columns. LIKE wildcards
     * in the input are escaped, so they match literally.
     *
     * @param  Builder<Employee>  $query
     * @param  list<string>  $columns
     */
    private function applyContains(Builder $query, array $columns, ?string $value): void
    {
        if ($value === null || trim($value) === '') {
            return;
        }

        $pattern = '%'.addcslashes(trim($value), '\\%_').'%';

        $query->whereAny($columns, self::CASE_INSENSITIVE_LIKE, $pattern);
    }
}
