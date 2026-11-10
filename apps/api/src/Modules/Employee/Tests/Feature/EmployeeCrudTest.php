<?php

declare(strict_types=1);

use Meridian\Modules\Audit\Infrastructure\Persistence\Models\AuditEvent;
use Meridian\Modules\Employee\Infrastructure\Persistence\Models\Employee;

function validEmployeePayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Nimal',
        'last_name' => 'Perera',
        'work_email' => 'Nimal.Perera@Meridian.test',
        'job_title' => 'Senior Engineer',
        'employment_type' => 'full_time',
        'hire_date' => '2022-04-01',
    ], $overrides);
}

beforeEach(function (): void {
    $this->hrToken = tokenForRole('hr_admin');
});

it('creates an employee with a generated number, ETag and Location', function (): void {
    $response = $this->withToken($this->hrToken)->postJson(route('employees.store'), validEmployeePayload());

    $id = $response->json('data.id');

    $response->assertCreated()
        ->assertHeader('ETag', '"v1"')
        ->assertHeader('Location', route('employees.show', $id))
        ->assertJsonPath('data.employee_number', 'EMP-000001')
        ->assertJsonPath('data.work_email', 'nimal.perera@meridian.test')
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.display_name', 'Nimal Perera')
        ->assertJsonPath('data.meta.version', 1)
        ->assertJsonPath('data.meta.permissions', ['update', 'delete', 'view_sensitive', 'update_sensitive']);

    $this->withToken($this->hrToken)->postJson(route('employees.store'), validEmployeePayload(['work_email' => 'second@meridian.test']))
        ->assertJsonPath('data.employee_number', 'EMP-000002');
});

it('writes an audit event in the same transaction, masking personal data', function (): void {
    $id = $this->withToken($this->hrToken)
        ->postJson(route('employees.store'), validEmployeePayload(['phone' => '+94 77 123 4567']))
        ->json('data.id');

    $event = AuditEvent::query()->where('subject_id', $id)->sole();

    expect($event->action)->toBe('employee.created')
        ->and($event->actor_id)->not->toBeNull()
        ->and($event->changes['job_title'])->toBe([null, 'Senior Engineer'])
        ->and($event->changes['phone'])->toBe([null, '***']);
});

it('encrypts personal data at rest', function (): void {
    $id = $this->withToken($this->hrToken)
        ->postJson(route('employees.store'), validEmployeePayload(['phone' => '+94 77 123 4567']))
        ->json('data.id');

    $stored = Employee::query()->whereKey($id)->toBase()->value('phone');

    expect($stored)->not->toContain('123')
        ->and(Employee::query()->findOrFail($id)->phone)->toBe('+94 77 123 4567');
});

it('shows an employee with its manager', function (): void {
    $manager = Employee::factory()->create(['first_name' => 'Ayesha', 'last_name' => 'Fernando']);
    $employee = Employee::factory()->reportingTo($manager)->create();

    $this->withToken($this->hrToken)
        ->getJson(route('employees.show', $employee->id))
        ->assertOk()
        ->assertHeader('ETag', '"v1"')
        ->assertJsonPath('data.manager.display_name', 'Ayesha Fernando');
});

it('returns 404 for unknown employees', function (): void {
    $this->withToken($this->hrToken)
        ->getJson(route('employees.show', '0193a4c2-0000-7000-8000-000000000000'))
        ->assertNotFound()
        ->assertJsonPath('code', 'employee_not_found');
});

it('updates only the sent fields, bumps the version and audits the diff', function (): void {
    $employee = Employee::factory()->create(['job_title' => 'Engineer']);

    $this->withToken($this->hrToken)
        ->withHeader('If-Match', '"v1"')
        ->patchJson(route('employees.update', $employee->id), ['job_title' => 'Lead Engineer'])
        ->assertOk()
        ->assertHeader('ETag', '"v2"')
        ->assertJsonPath('data.job_title', 'Lead Engineer')
        ->assertJsonPath('data.last_name', $employee->last_name);

    expect(AuditEvent::query()->where('action', 'employee.updated')->sole()->changes)
        ->toBe(['job_title' => ['Engineer', 'Lead Engineer']]);
});

it('does not bump the version or audit when nothing changed', function (): void {
    $employee = Employee::factory()->create(['job_title' => 'Engineer']);

    $this->withToken($this->hrToken)
        ->withHeader('If-Match', '"v1"')
        ->patchJson(route('employees.update', $employee->id), ['job_title' => 'Engineer'])
        ->assertOk()
        ->assertHeader('ETag', '"v1"');

    expect(AuditEvent::query()->where('action', 'employee.updated')->exists())->toBeFalse();
});

it('requires If-Match on updates', function (): void {
    $employee = Employee::factory()->create();

    $this->withToken($this->hrToken)
        ->patchJson(route('employees.update', $employee->id), ['job_title' => 'X'])
        ->assertStatus(428)
        ->assertJsonPath('code', 'precondition_required');
});

it('rejects updates based on a stale version', function (): void {
    $employee = Employee::factory()->create();

    $this->withToken($this->hrToken)->withHeader('If-Match', '"v1"')
        ->patchJson(route('employees.update', $employee->id), ['job_title' => 'First edit'])
        ->assertOk();

    $this->withToken($this->hrToken)->withHeader('If-Match', '"v1"')
        ->patchJson(route('employees.update', $employee->id), ['job_title' => 'Lost update'])
        ->assertStatus(412)
        ->assertJsonPath('code', 'version_mismatch');
});

it('validates input', function (array $payload, string $field, string $rule): void {
    $this->withToken($this->hrToken)
        ->postJson(route('employees.store'), validEmployeePayload($payload))
        ->assertUnprocessable()
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonPath("errors.{$field}.0.code", $rule);
})->with([
    'missing first name' => [['first_name' => null], 'first_name', 'required'],
    'invalid e-mail' => [['work_email' => 'not-an-email'], 'work_email', 'email'],
    'unknown employment type' => [['employment_type' => 'freelance'], 'employment_type', 'enum'],
    'bad date' => [['hire_date' => '01/04/2022'], 'hire_date', 'date_format'],
    'too young' => [['date_of_birth' => now()->subYears(10)->format('Y-m-d')], 'date_of_birth', 'before'],
    'server-managed field' => [['employee_number' => 'EMP-999'], 'employee_number', 'prohibited'],
]);

it('rejects a duplicate work e-mail', function (): void {
    Employee::factory()->create(['work_email' => 'taken@meridian.test']);

    $this->withToken($this->hrToken)
        ->postJson(route('employees.store'), validEmployeePayload(['work_email' => 'TAKEN@meridian.test']))
        ->assertConflict()
        ->assertJsonPath('code', 'work_email_in_use');
});

it('prevents reporting cycles', function (): void {
    $manager = Employee::factory()->create();
    $report = Employee::factory()->reportingTo($manager)->create();

    $this->withToken($this->hrToken)->withHeader('If-Match', '"v1"')
        ->patchJson(route('employees.update', $manager->id), ['manager_id' => $report->id])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'invalid_manager');
});

it('requires a termination date when terminating', function (): void {
    $employee = Employee::factory()->create();

    $this->withToken($this->hrToken)->withHeader('If-Match', '"v1"')
        ->patchJson(route('employees.update', $employee->id), ['status' => 'terminated'])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'invalid_employment_dates');
});
