<?php

declare(strict_types=1);

use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeStatus;
use Meridian\Modules\Employee\Infrastructure\Persistence\Models\Employee;
use Meridian\Modules\Organisation\Infrastructure\Persistence\Models\Department;

beforeEach(function (): void {
    $this->token = tokenForRole('hr_admin');
});

it('assigns employees to existing departments only', function (): void {
    $department = Department::factory()->create();
    $employee = Employee::factory()->create();

    $this->withToken($this->token)->withHeader('If-Match', '"v1"')
        ->patchJson(route('employees.update', $employee->id), ['department_id' => $department->id])
        ->assertOk()
        ->assertJsonPath('data.department_id', $department->id);

    $this->withToken($this->token)->withHeader('If-Match', '"v2"')
        ->patchJson(route('employees.update', $employee->id), ['department_id' => '0193a4c2-0000-7000-8000-000000000000'])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'invalid_department');
});

it('filters the directory by department', function (): void {
    $engineering = Department::factory()->create();
    Employee::factory()->create(['last_name' => 'Engineer', 'department_id' => $engineering->id]);
    Employee::factory()->create(['last_name' => 'Elsewhere']);

    $this->withToken($this->token)
        ->getJson(route('employees.index', ['filter' => ['department_id' => $engineering->id]]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.last_name', 'Engineer');
});

it('reports headcount in total and per status, excluding deleted employees', function (): void {
    Employee::factory()->count(3)->withStatus(EmployeeStatus::Active)->create();
    Employee::factory()->withStatus(EmployeeStatus::OnLeave)->create();
    Employee::factory()->create()->delete();

    $this->withToken($this->token)->getJson(route('employees.headcount'))
        ->assertOk()
        ->assertJsonPath('data.total', 4)
        ->assertJsonPath('data.by_status.active', 3)
        ->assertJsonPath('data.by_status.on_leave', 1)
        ->assertJsonPath('data.by_status.terminated', 0);
});
