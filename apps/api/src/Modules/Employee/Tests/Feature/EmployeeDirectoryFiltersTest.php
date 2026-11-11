<?php

declare(strict_types=1);

use Meridian\Modules\Employee\Infrastructure\Persistence\Models\Employee;
use Meridian\Modules\Organisation\Infrastructure\Persistence\Models\Department;

beforeEach(function (): void {
    $this->token = tokenForRole('hr_admin');
});

/**
 * @param  array<string, mixed>  $query
 * @return list<string>
 */
function directoryNumbers(object $test, array $query): array
{
    return $test->withToken($test->token)->getJson(route('employees.index', $query))->assertOk()->json('data.*.employee_number');
}

it('filters by name, employee number, e-mail and job title', function (): void {
    Employee::factory()->create(['employee_number' => 'EMP-000101', 'first_name' => 'Amaya', 'last_name' => 'Silva', 'work_email' => 'amaya@meridian.test', 'job_title' => 'Data Analyst']);
    Employee::factory()->create(['employee_number' => 'EMP-000102', 'first_name' => 'Ruwan', 'last_name' => 'Amarasinghe', 'work_email' => 'ruwan@meridian.test', 'job_title' => 'Platform Engineer']);

    expect(directoryNumbers($this, ['filter' => ['name' => 'ama']]))->toEqualCanonicalizing(['EMP-000101', 'EMP-000102'])
        ->and(directoryNumbers($this, ['filter' => ['name' => 'silva']]))->toBe(['EMP-000101'])
        ->and(directoryNumbers($this, ['filter' => ['employee_number' => '0102']]))->toBe(['EMP-000102'])
        ->and(directoryNumbers($this, ['filter' => ['email' => 'AMAYA@']]))->toBe(['EMP-000101'])
        ->and(directoryNumbers($this, ['filter' => ['job_title' => 'engineer']]))->toBe(['EMP-000102'])
        ->and(directoryNumbers($this, ['filter' => ['name' => 'ama', 'job_title' => 'analyst']]))->toBe(['EMP-000101']);
});

it('treats LIKE wildcards in filters literally', function (): void {
    Employee::factory()->create(['job_title' => 'Engineer']);

    expect(directoryNumbers($this, ['filter' => ['job_title' => '%']]))->toBe([]);
});

it('sorts by department name across pages', function (): void {
    $finance = Department::factory()->create(['name' => 'Finance']);
    $alpha = Department::factory()->create(['name' => 'Analytics']);

    $payload = static fn (string $email, ?string $departmentId): array => [
        'first_name' => 'Test', 'last_name' => 'Person', 'work_email' => $email, 'job_title' => 'Analyst',
        'employment_type' => 'full_time', 'hire_date' => '2024-01-01', 'department_id' => $departmentId,
    ];

    foreach ([['f@meridian.test', $finance->id], ['a@meridian.test', $alpha->id], ['n@meridian.test', null]] as [$email, $departmentId]) {
        $this->withToken($this->token)->postJson(route('employees.store'), $payload($email, $departmentId))->assertCreated();
    }

    $first = $this->withToken($this->token)->getJson(route('employees.index', ['sort' => '-department', 'per_page' => 2]))->assertOk();
    $second = $this->withToken($this->token)->getJson(route('employees.index', ['sort' => '-department', 'per_page' => 2, 'cursor' => $first->json('meta.next_cursor')]))->assertOk();

    expect([...$first->json('data.*.department_name'), ...$second->json('data.*.department_name')])->toBe(['Finance', 'Analytics', null]);
});

it('keeps the department name in sync when a department is renamed', function (): void {
    $department = Department::factory()->create(['name' => 'People Ops']);
    $employee = Employee::factory()->create(['department_id' => $department->id, 'department_name' => 'People Ops']);

    $this->withToken($this->token)->withHeader('If-Match', '"v1"')
        ->patchJson(route('departments.update', $department->id), ['name' => 'People & Culture'])
        ->assertOk();

    $this->withToken($this->token)->getJson(route('employees.show', $employee->id))
        ->assertJsonPath('data.department_name', 'People & Culture')
        ->assertJsonPath('data.meta.version', 1);
});

it('rejects unknown filters', function (): void {
    $this->withToken($this->token)->getJson(route('employees.index', ['filter' => ['salary' => '1']]))->assertUnprocessable();
});
