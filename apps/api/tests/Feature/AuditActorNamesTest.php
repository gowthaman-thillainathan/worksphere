<?php

declare(strict_types=1);

use Meridian\Modules\Audit\Contracts\AuditRecord;
use Meridian\Modules\Audit\Contracts\AuditRecorder;

/*
| Audit owns ActorDirectory; Identity implements it. Checked end to end.
*/

it('shows who made each change', function (): void {
    $user = userWithRoles(['hr_admin', 'auditor']);
    $user->forceFill(['name' => 'Dilani Jayawardena'])->save();
    $token = issueTokensFor($user)->accessToken;

    $this->withToken($token)->postJson(route('employees.store'), [
        'first_name' => 'Nimal', 'last_name' => 'Perera', 'work_email' => 'nimal@meridian.test',
        'job_title' => 'Engineer', 'employment_type' => 'full_time', 'hire_date' => '2024-01-01',
    ])->assertCreated();

    $this->withToken($token)->getJson(route('audit.events.index'))
        ->assertOk()
        ->assertJsonPath('data.0.actor', ['type' => 'user', 'id' => $user->id, 'name' => 'Dilani Jayawardena']);
});

it('leaves the name empty for system events', function (): void {
    app(AuditRecorder::class)->record(new AuditRecord('employee.updated', 'employee', '0193a4c2-0000-7000-8000-000000000001'));

    $this->withToken(tokenForRole('auditor'))->getJson(route('audit.events.index'))
        ->assertJsonPath('data.0.actor', ['type' => 'system', 'id' => null, 'name' => null]);
});
