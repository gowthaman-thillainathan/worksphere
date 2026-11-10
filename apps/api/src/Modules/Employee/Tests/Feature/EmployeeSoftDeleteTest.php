<?php

declare(strict_types=1);

use Meridian\Modules\Audit\Infrastructure\Persistence\Models\AuditEvent;
use Meridian\Modules\Employee\Infrastructure\Persistence\Models\Employee;

beforeEach(function (): void {
    $this->token = tokenForRole('hr_admin');
});

it('soft deletes an employee and keeps the row', function (): void {
    $employee = Employee::factory()->create();

    $this->withToken($this->token)->withHeader('If-Match', '"v1"')
        ->deleteJson(route('employees.destroy', $employee->id))
        ->assertNoContent();

    $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    $this->withToken($this->token)->getJson(route('employees.show', $employee->id))->assertNotFound();
    expect(AuditEvent::query()->where('action', 'employee.deleted')->where('subject_id', $employee->id)->exists())->toBeTrue();
});

it('requires If-Match to delete', function (): void {
    $employee = Employee::factory()->create();

    $this->withToken($this->token)->deleteJson(route('employees.destroy', $employee->id))->assertStatus(428);
});

it('refuses to delete a manager who still has direct reports', function (): void {
    $manager = Employee::factory()->create();
    Employee::factory()->reportingTo($manager)->create();

    $this->withToken($this->token)->withHeader('If-Match', '"v1"')
        ->deleteJson(route('employees.destroy', $manager->id))
        ->assertConflict()
        ->assertJsonPath('code', 'employee_has_direct_reports');
});

it('restores a soft-deleted employee', function (): void {
    $employee = Employee::factory()->create();
    $employee->delete();

    $this->withToken($this->token)
        ->postJson(route('employees.restore', $employee->id))
        ->assertOk()
        ->assertJsonPath('data.deleted_at', null)
        ->assertHeader('ETag', '"v2"');

    expect(AuditEvent::query()->where('action', 'employee.restored')->exists())->toBeTrue();
});

it('refuses to restore when the work e-mail was reused meanwhile', function (): void {
    $former = Employee::factory()->create(['work_email' => 'shared@meridian.test']);
    $former->delete();
    Employee::factory()->create(['work_email' => 'shared@meridian.test']);

    $this->withToken($this->token)
        ->postJson(route('employees.restore', $former->id))
        ->assertConflict()
        ->assertJsonPath('code', 'work_email_in_use');
});
