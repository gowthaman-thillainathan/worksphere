<?php

declare(strict_types=1);

use Meridian\Modules\Audit\Infrastructure\Persistence\Models\AuditEvent;
use Meridian\Modules\Employee\Infrastructure\Persistence\Models\Employee;
use Meridian\Modules\Organisation\Infrastructure\Persistence\Models\Department;

beforeEach(function (): void {
    $this->token = tokenForRole('hr_admin');
});

it('creates a department with an upper-case code, ETag and audit event', function (): void {
    $parent = Department::factory()->create(['name' => 'Technology']);

    $response = $this->withToken($this->token)->postJson(route('departments.store'), [
        'code' => 'eng', 'name' => 'Engineering', 'parent_id' => $parent->id,
    ]);

    $response->assertCreated()
        ->assertHeader('ETag', '"v1"')
        ->assertJsonPath('data.code', 'ENG')
        ->assertJsonPath('data.parent.name', 'Technology')
        ->assertJsonPath('data.meta.permissions', ['update', 'delete']);

    expect(AuditEvent::query()->where('action', 'department.created')->sole()->changes['code'])->toBe([null, 'ENG']);
});

it('lists every department by name with a total', function (): void {
    Department::factory()->create(['name' => 'Sales']);
    Department::factory()->create(['name' => 'Engineering']);

    $this->withToken($this->token)->getJson(route('departments.index'))
        ->assertOk()
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('data.0.name', 'Engineering');
});

it('rejects duplicate codes regardless of case', function (): void {
    Department::factory()->create(['code' => 'ENG']);

    $this->withToken($this->token)->postJson(route('departments.store'), ['code' => 'eng', 'name' => 'Other'])
        ->assertConflict()
        ->assertJsonPath('code', 'department_code_in_use');
});

it('updates with optimistic locking and refuses hierarchy cycles', function (): void {
    $parent = Department::factory()->create();
    $child = Department::factory()->under($parent)->create();

    $this->withToken($this->token)->withHeader('If-Match', '"v1"')
        ->patchJson(route('departments.update', $parent->id), ['name' => 'Renamed'])
        ->assertOk()
        ->assertHeader('ETag', '"v2"');

    $this->withToken($this->token)->withHeader('If-Match', '"v2"')
        ->patchJson(route('departments.update', $parent->id), ['parent_id' => $child->id])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'invalid_parent_department');

    $this->withToken($this->token)->withHeader('If-Match', '"v1"')
        ->patchJson(route('departments.update', $parent->id), ['name' => 'Stale'])
        ->assertStatus(412);
});

it('deletes an unused department', function (): void {
    $department = Department::factory()->create();

    $this->withToken($this->token)->withHeader('If-Match', '"v1"')
        ->deleteJson(route('departments.destroy', $department->id))
        ->assertNoContent();

    $this->assertModelMissing($department);
});

it('refuses to delete a department with sub-departments or employees', function (): void {
    $parent = Department::factory()->create();
    Department::factory()->under($parent)->create();
    $staffed = Department::factory()->create();
    Employee::factory()->create(['department_id' => $staffed->id])->delete();

    foreach ([$parent, $staffed] as $department) {
        $this->withToken($this->token)->withHeader('If-Match', '"v1"')
            ->deleteJson(route('departments.destroy', $department->id))
            ->assertConflict()
            ->assertJsonPath('code', 'department_in_use');
    }
});

it('lets viewers read but not change departments', function (): void {
    $department = Department::factory()->create();
    $auditor = tokenForRole('auditor');

    $this->withToken($auditor)->getJson(route('departments.show', $department->id))
        ->assertOk()
        ->assertJsonPath('data.meta.permissions', []);
    $this->withToken($auditor)->postJson(route('departments.store'), ['code' => 'X', 'name' => 'X'])->assertForbidden();
    $this->withToken(tokenForRole('tenant_admin'))->getJson(route('departments.index'))->assertForbidden();
});

it('validates the payload', function (): void {
    $this->withToken($this->token)->postJson(route('departments.store'), ['code' => 'has space'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.code.0.code', 'regex')
        ->assertJsonPath('errors.name.0.code', 'required');
});
