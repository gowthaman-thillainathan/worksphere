<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Meridian\Modules\Employee\Domain\Exceptions\InvalidEmploymentDates;
use Meridian\Modules\Employee\Domain\Exceptions\InvalidManager;
use Meridian\Modules\Employee\Domain\Exceptions\WorkEmailInUse;
use Meridian\Modules\Employee\Domain\Repositories\EmployeeRepository;
use Meridian\Modules\Employee\Domain\Services\EmployeeInvariants;
use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeSearchCriteria;
use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeStatus;
use Meridian\Modules\Employee\Infrastructure\Persistence\Models\Employee;
use Meridian\Shared\Domain\ValueObjects\CursorPage;

/**
 * Reporting lines as a map of employee id => manager id.
 */
final class ReportingLineRepository implements EmployeeRepository
{
    /**
     * @param  array<string, string|null>  $managers
     * @param  list<string>  $emails
     */
    public function __construct(private array $managers, private array $emails = []) {}

    public function find(string $id): ?Employee
    {
        return array_key_exists($id, $this->managers) ? new Employee : null;
    }

    public function managerIdOf(string $employeeId): ?string
    {
        return $this->managers[$employeeId] ?? null;
    }

    public function workEmailInUse(string $workEmail, ?string $exceptEmployeeId = null): bool
    {
        return in_array($workEmail, $this->emails, true);
    }

    public function findForUpdate(string $id, bool $includingTrashed = false): ?Employee
    {
        return null;
    }

    public function search(EmployeeSearchCriteria $criteria): CursorPage
    {
        return new CursorPage([], 25, null, null);
    }

    public function save(Employee $employee): void {}

    public function softDelete(Employee $employee): void {}

    public function restore(Employee $employee): void {}

    public function hasActiveDirectReports(string $employeeId): bool
    {
        return false;
    }

    public function nextSequenceValue(): int
    {
        return 1;
    }
}

// ceo <- vp <- lead <- engineer
function invariants(array $emails = []): EmployeeInvariants
{
    return new EmployeeInvariants(new ReportingLineRepository(
        ['ceo' => null, 'vp' => 'ceo', 'lead' => 'vp', 'engineer' => 'lead'],
        $emails,
    ));
}

it('accepts a manager higher up or in another branch', function (): void {
    invariants()->assertValidManager('engineer', 'vp');
    invariants()->assertValidManager(null, 'lead');
    invariants()->assertValidManager('lead', null);
})->throwsNoExceptions();

it('rejects an employee managing themselves', function (): void {
    invariants()->assertValidManager('lead', 'lead');
})->throws(InvalidManager::class, 'cannot be their own manager');

it('rejects a manager who reports to the employee (cycle)', function (): void {
    invariants()->assertValidManager('vp', 'engineer');
})->throws(InvalidManager::class, 'reporting cycle');

it('rejects an unknown manager', function (): void {
    invariants()->assertValidManager('lead', 'ghost');
})->throws(InvalidManager::class, 'does not exist');

it('rejects a work e-mail that is already used', function (): void {
    invariants(['taken@meridian.test'])->assertWorkEmailAvailable('taken@meridian.test');
})->throws(WorkEmailInUse::class);

it('requires a termination date for terminated employees', function (): void {
    EmployeeInvariants::assertValidDates(EmployeeStatus::Terminated, CarbonImmutable::parse('2024-01-01'), null);
})->throws(InvalidEmploymentDates::class, 'termination date is required');

it('rejects a termination before the hire date', function (): void {
    EmployeeInvariants::assertValidDates(EmployeeStatus::Terminated, CarbonImmutable::parse('2024-01-01'), CarbonImmutable::parse('2023-12-31'));
})->throws(InvalidEmploymentDates::class, 'cannot be before the hire date');

it('sorts the directory by name when no sort is given', function (): void {
    $sort = (new EmployeeSearchCriteria)->sort;

    expect(array_map(static fn ($s) => $s->field->value, $sort))->toBe(['last_name', 'first_name']);
});
