<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Infrastructure\Persistence\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Meridian\Modules\Employee\Domain\Repositories\EmployeeRepository;
use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeSearchCriteria;
use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeStatus;
use Meridian\Modules\Employee\Domain\ValueObjects\EmploymentType;
use Meridian\Modules\Employee\Domain\ValueObjects\TrashedFilter;
use Meridian\Modules\Employee\Infrastructure\Persistence\Models\Employee;
use Meridian\Modules\Employee\Infrastructure\Persistence\Models\EmployeeNumberSequence;
use Meridian\Shared\Domain\ValueObjects\CursorPage;

final class EloquentEmployeeRepository implements EmployeeRepository
{
    private const MANAGER_COLUMNS = 'manager:id,first_name,last_name,preferred_name';

    private const SEARCH_COLUMNS = ['first_name', 'last_name', 'preferred_name', 'employee_number', 'work_email'];

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
        $this->applySearch($query, $criteria->search);

        foreach ($criteria->sort as $sort) {
            $query->orderBy($sort->field->value, $sort->direction->value);
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

    public function save(Employee $employee): void
    {
        $employee->save();
    }

    public function softDelete(Employee $employee): void
    {
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
     * Case-insensitive substring match on name, number and work e-mail.
     *
     * @param  Builder<Employee>  $query
     */
    private function applySearch(Builder $query, ?string $search): void
    {
        if ($search === null || trim($search) === '') {
            return;
        }

        $pattern = '%'.addcslashes(trim($search), '\\%_').'%';

        $query->whereAny(self::SEARCH_COLUMNS, self::CASE_INSENSITIVE_LIKE, $pattern);
    }
}
