<?php

declare(strict_types=1);

use Meridian\Modules\Audit\Infrastructure\Persistence\Models\AuditEvent;
use Meridian\Modules\Employee\Infrastructure\Persistence\Models\Employee;

const DELETION_REASON = ['reason' => 'Duplicate record created during onboarding.'];

beforeEach(function (): void {
    $this->token = tokenForRole('hr_admin');
});

it('soft deletes an employee with a reason and keeps the row', function (): void {
    $employee = Employee::factory()->create();

    $this->withToken($this->token)->withHeader('If-Match', '"v1"')
        ->deleteJson(route('employees.destroy', $employee->id), DELETION_REASON)
        ->assertNoContent();

    $this->assertSoftDeleted('employees', ['id' => $employee->id, 'deletion_reason' => DELETION_REASON['reason'], 'version' => 2]);
    $this->withToken($this->token)->getJson(route('employees.show', $employee->id))->assertNotFound();

    $event = AuditEvent::query()->where('action', 'employee.deleted')->where('subject_id', $employee->id)->sole();
    expect($event->getAttribute('changes'))->toBe(['deletion_reason' => [null, DELETION_REASON['reason']]]);
});

it('requires a reason to delete', function (string $reason): void {
    $employee = Employee::factory()->create();

    $this->withToken($this->token)->withHeader('If-Match', '"v1"')
        ->deleteJson(route('employees.destroy', $employee->id), ['reason' => $reason])
        ->assertUnprocessable()
        ->assertJsonStructure(['errors' => ['reason']]);

    $this->assertNotSoftDeleted('employees', ['id' => $employee->id]);
})->with(['missing' => '', 'too short' => 'dup', 'too long' => str_repeat('x', 501)]);

it('shows the reason on deleted employees in the directory', function (): void {
    $employee = Employee::factory()->create();

    $this->withToken($this->token)->withHeader('If-Match', '"v1"')
        ->deleteJson(route('employees.destroy', $employee->id), DELETION_REASON)
        ->assertNoContent();

    $this->withToken($this->token)->getJson(route('employees.index', ['filter' => ['trashed' => 'only']]))
        ->assertOk()
        ->assertJsonPath('data.0.deletion_reason', DELETION_REASON['reason']);
});

it('requires If-Match to delete', function (): void {
    $employee = Employee::factory()->create();

    $this->withToken($this->token)->deleteJson(route('employees.destroy', $employee->id), DELETION_REASON)->assertStatus(428);
});

it('refuses to delete a manager who still has direct reports', function (): void {
    $manager = Employee::factory()->create();
    Employee::factory()->reportingTo($manager)->create();

    $this->withToken($this->token)->withHeader('If-Match', '"v1"')
        ->deleteJson(route('employees.destroy', $manager->id), DELETION_REASON)
        ->assertConflict()
        ->assertJsonPath('code', 'employee_has_direct_reports');
});

it('restores a soft-deleted employee and clears the reason', function (): void {
    $employee = Employee::factory()->create(['deletion_reason' => DELETION_REASON['reason']]);
    $employee->delete();

    $this->withToken($this->token)
        ->postJson(route('employees.restore', $employee->id))
        ->assertOk()
        ->assertJsonPath('data.deleted_at', null)
        ->assertJsonPath('data.deletion_reason', null)
        ->assertHeader('ETag', '"v2"');

    $event = AuditEvent::query()->where('action', 'employee.restored')->sole();
    expect($event->getAttribute('changes'))->toBe(['deletion_reason' => [DELETION_REASON['reason'], null]]);
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
