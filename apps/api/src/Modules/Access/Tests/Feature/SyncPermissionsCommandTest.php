<?php

declare(strict_types=1);

use Meridian\Modules\Access\Contracts\Permission;
use Meridian\Modules\Access\Domain\ValueObjects\SystemRole;
use Meridian\Modules\Access\Infrastructure\Persistence\Models\PermissionRecord;
use Meridian\Modules\Access\Infrastructure\Persistence\Models\Role;

it('mirrors the catalogue and system roles into the database', function (): void {
    $this->artisan('access:sync-permissions')->assertSuccessful();

    expect(PermissionRecord::query()->count())->toBe(count(Permission::cases()))
        ->and(Role::query()->where('is_system', true)->count())->toBe(count(SystemRole::cases()))
        ->and(Role::query()->where('key', 'payroll_officer')->firstOrFail()->permissions()->pluck('key')->sort()->values()->all())
        ->toBe(['compensation.manage', 'compensation.view', 'employee.view']);
});

it('is idempotent', function (): void {
    $this->artisan('access:sync-permissions')->assertSuccessful();
    $this->artisan('access:sync-permissions')->assertSuccessful();

    expect(PermissionRecord::query()->count())->toBe(count(Permission::cases()));
});

it('removes permissions that no longer exist in code', function (): void {
    PermissionRecord::query()->create(['key' => 'legacy.permission', 'module' => 'legacy', 'description' => 'Old']);

    $this->artisan('access:sync-permissions')->assertSuccessful();

    $this->assertDatabaseMissing('permissions', ['key' => 'legacy.permission']);
});
