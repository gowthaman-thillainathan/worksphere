<?php

declare(strict_types=1);

use Meridian\Modules\Employee\Infrastructure\Persistence\Models\Employee;

it('hides personal data from users without employee.view.sensitive', function (): void {
    $employee = Employee::factory()->create(['phone' => '+94 77 123 4567']);

    $this->withToken(tokenForRole('auditor'))
        ->getJson(route('employees.show', $employee->id))
        ->assertOk()
        ->assertJsonMissingPath('data.phone')
        ->assertJsonMissingPath('data.date_of_birth')
        ->assertJsonPath('data.meta.permissions', []);

    $this->withToken(tokenForRole('hr_admin'))
        ->getJson(route('employees.show', $employee->id))
        ->assertJsonPath('data.phone', '+94 77 123 4567');
});

it('forbids writing personal data without employee.update.sensitive', function (): void {
    // payroll officers can view employees but not edit personal data
    $this->withToken(tokenForRole('payroll_officer'))
        ->postJson(route('employees.store'), ['first_name' => 'X', 'phone' => '+94 77 000 0000'])
        ->assertForbidden();
});

it('enforces a permission per operation', function (string $role, string $method, string $route, int $status): void {
    $employee = Employee::factory()->create();

    $this->withToken(tokenForRole($role))
        ->withHeader('If-Match', '"v1"')
        ->json($method, route($route, $employee->id))
        ->assertStatus($status);
})->with([
    'auditor can list' => ['auditor', 'GET', 'employees.index', 200],
    'auditor cannot update' => ['auditor', 'PATCH', 'employees.update', 403],
    'auditor cannot delete' => ['auditor', 'DELETE', 'employees.destroy', 403],
    'payroll cannot delete' => ['payroll_officer', 'DELETE', 'employees.destroy', 403],
    'tenant admin cannot read HR data' => ['tenant_admin', 'GET', 'employees.show', 403],
]);

it('requires authentication', function (): void {
    $this->getJson(route('employees.index'))->assertUnauthorized();
});
