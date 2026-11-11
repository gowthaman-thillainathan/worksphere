<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Notification;
use Meridian\Modules\Access\Contracts\RoleAssigner;
use Meridian\Modules\Audit\Infrastructure\Persistence\Models\AuditEvent;
use Meridian\Modules\Employee\Infrastructure\Persistence\Models\Employee;
use Meridian\Modules\Identity\Infrastructure\Notifications\AccountInvitationNotification;
use Meridian\Modules\Identity\Infrastructure\Persistence\Models\User;

/*
| Sign-in accounts for employees (ADR-0007). Managing them takes user.manage
| and access.manage, so the HR administrator alone cannot.
*/

const ACCOUNT_MANAGER_ROLES = ['hr_admin', 'tenant_admin'];

beforeEach(function (): void {
    Notification::fake();
    $this->token = tokenForRoles(ACCOUNT_MANAGER_ROLES);
});

function employeePayloadWithAccount(string $role): array
{
    return [
        'first_name' => 'Ishara',
        'last_name' => 'Fernando',
        'work_email' => 'ishara.fernando@meridian.test',
        'job_title' => 'Payroll Specialist',
        'employment_type' => 'full_time',
        'hire_date' => '2025-02-01',
        'account' => ['role' => $role],
    ];
}

it('creates an employee together with an invited account', function (): void {
    $response = $this->withToken($this->token)
        ->postJson(route('employees.store'), employeePayloadWithAccount('payroll_officer'))
        ->assertCreated()
        ->assertJsonPath('data.has_account', true);

    $user = User::query()->where('email', 'ishara.fernando@meridian.test')->sole();

    expect($user->password)->toBeNull()
        ->and($user->email_verified_at)->toBeNull()
        ->and(app(RoleAssigner::class)->rolesOf($user->id))->toBe(['payroll_officer'])
        ->and(Employee::query()->find($response->json('data.id'))->user_id)->toBe($user->id)
        ->and(AuditEvent::query()->where('action', 'employee.account_created')->sole()->getAttribute('changes'))
        ->toBe(['role' => [null, 'payroll_officer']]);

    Notification::assertSentTo($user, AccountInvitationNotification::class);
});

it('creates nothing when the role does not exist', function (): void {
    $this->withToken($this->token)
        ->postJson(route('employees.store'), employeePayloadWithAccount('chief_wizard'))
        ->assertUnprocessable()
        ->assertJsonPath('code', 'invalid_role');

    expect(Employee::query()->count())->toBe(0)
        ->and(User::query()->where('email', 'ishara.fernando@meridian.test')->exists())->toBeFalse();

    Notification::assertNothingSent();
});

it('refuses an account for an address that already has one', function (): void {
    User::factory()->create(['email' => 'ishara.fernando@meridian.test']);

    $this->withToken($this->token)
        ->postJson(route('employees.store'), employeePayloadWithAccount('auditor'))
        ->assertConflict()
        ->assertJsonPath('code', 'email_already_taken');

    expect(Employee::query()->count())->toBe(0);
});

it('does not let an HR administrator alone create accounts', function (): void {
    $this->withToken(tokenForRole('hr_admin'))
        ->postJson(route('employees.store'), employeePayloadWithAccount('auditor'))
        ->assertForbidden();

    $employee = Employee::factory()->create();

    $this->withToken(tokenForRole('hr_admin'))
        ->postJson(route('employees.account.store', $employee->id), ['role' => 'auditor'])
        ->assertForbidden();
});

it('tells the UI who may manage accounts', function (): void {
    $employee = Employee::factory()->create();

    $this->withToken($this->token)->getJson(route('employees.show', $employee->id))
        ->assertJsonPath('data.meta.permissions', ['update', 'delete', 'view_sensitive', 'update_sensitive', 'manage_account']);

    $this->withToken(tokenForRole('hr_admin'))->getJson(route('employees.show', $employee->id))
        ->assertJsonPath('data.meta.permissions', ['update', 'delete', 'view_sensitive', 'update_sensitive']);
});

it('gives an existing employee an account and shows it', function (): void {
    $employee = Employee::factory()->create(['work_email' => 'kasun@meridian.test']);

    $this->withToken($this->token)
        ->postJson(route('employees.account.store', $employee->id), ['role' => 'auditor'])
        ->assertCreated()
        ->assertJsonPath('data.email', 'kasun@meridian.test')
        ->assertJsonPath('data.role', 'auditor')
        ->assertJsonPath('data.email_verified', false);

    $this->withToken($this->token)->getJson(route('employees.account.show', $employee->id))
        ->assertOk()
        ->assertJsonPath('data.role', 'auditor');

    $this->withToken($this->token)
        ->postJson(route('employees.account.store', $employee->id), ['role' => 'auditor'])
        ->assertConflict()
        ->assertJsonPath('code', 'employee_account_exists');
});

it('changes the role and records it on the employee', function (): void {
    $employee = Employee::factory()->create();
    $this->withToken($this->token)->postJson(route('employees.account.store', $employee->id), ['role' => 'auditor'])->assertCreated();

    $this->withToken($this->token)
        ->putJson(route('employees.account.role', $employee->id), ['role' => 'payroll_officer'])
        ->assertOk()
        ->assertJsonPath('data.role', 'payroll_officer');

    expect(AuditEvent::query()->where('action', 'employee.role_changed')->sole()->getAttribute('changes'))
        ->toBe(['role' => ['auditor', 'payroll_officer']]);

    // Unchanged role: no new event.
    $this->withToken($this->token)->putJson(route('employees.account.role', $employee->id), ['role' => 'payroll_officer'])->assertOk();
    expect(AuditEvent::query()->where('action', 'employee.role_changed')->count())->toBe(1);
});

it('reports a missing account as not found', function (): void {
    $employee = Employee::factory()->create();

    $this->withToken($this->token)->getJson(route('employees.account.show', $employee->id))
        ->assertNotFound()
        ->assertJsonPath('code', 'employee_account_not_found');

    $this->withToken($this->token)->putJson(route('employees.account.role', $employee->id), ['role' => 'auditor'])
        ->assertNotFound();
});
