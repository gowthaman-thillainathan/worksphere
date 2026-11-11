<?php

declare(strict_types=1);

use Meridian\Modules\Audit\Contracts\AuditRecord;
use Meridian\Modules\Audit\Contracts\AuditRecorder;
use Meridian\Modules\Audit\Infrastructure\Persistence\Models\AuditEvent;

function recordAudit(string $action, string $subjectId = '0193a4c2-0000-7000-8000-000000000001'): void
{
    app(AuditRecorder::class)->record(new AuditRecord($action, 'employee', $subjectId, ['status' => ['active', 'notice']]));
}

it('links every event to the previous one', function (): void {
    recordAudit('employee.created');
    recordAudit('employee.updated');

    [$first, $second] = AuditEvent::query()->orderBy('id')->get()->all();

    expect($first->prev_hash)->toBeNull()
        ->and($second->prev_hash)->toBe($first->hash)
        ->and($first->actor_type->value)->toBe('system');
});

it('verifies an intact chain', function (): void {
    recordAudit('employee.created');
    recordAudit('employee.updated');

    $this->artisan('audit:verify')->expectsOutputToContain('intact (2 events')->assertSuccessful();
});

it('detects an event that was edited behind the application\'s back', function (): void {
    recordAudit('employee.created');
    recordAudit('employee.updated');
    recordAudit('employee.deleted');

    $tampered = AuditEvent::query()->orderBy('id')->skip(1)->firstOrFail();
    AuditEvent::query()->whereKey($tampered->id)->update(['action' => 'employee.viewed']);

    $this->artisan('audit:verify')->expectsOutputToContain("broken at event #{$tampered->id}")->assertFailed();
});

it('refuses to modify events through the model', function (): void {
    recordAudit('employee.created');

    AuditEvent::query()->firstOrFail()->update(['action' => 'changed']);
})->throws(LogicException::class, 'immutable');

it('lists events newest first, filtered by subject', function (): void {
    recordAudit('employee.created', '0193a4c2-0000-7000-8000-00000000000a');
    recordAudit('employee.created', '0193a4c2-0000-7000-8000-00000000000b');
    recordAudit('employee.updated', '0193a4c2-0000-7000-8000-00000000000b');

    $this->withToken(tokenForRole('auditor'))
        ->getJson(route('audit.events.index', ['filter' => ['subject_id' => '0193a4c2-0000-7000-8000-00000000000b']]))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.action', 'employee.updated')
        ->assertJsonPath('data.0.changes.status', ['active', 'notice']);
});

it('requires audit.view', function (): void {
    $this->withToken(tokenForRole('hr_admin'))->getJson(route('audit.events.index'))->assertForbidden();
});
