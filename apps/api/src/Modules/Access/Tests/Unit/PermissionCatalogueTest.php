<?php

declare(strict_types=1);

use Meridian\Modules\Access\Contracts\Permission;
use Meridian\Modules\Access\Domain\ValueObjects\SystemRole;

it('names every permission as module.action', function (Permission $permission): void {
    expect($permission->value)->toMatch('/^[a-z]+\.[a-z_.]+$/')
        ->and($permission->module())->toBe(explode('.', $permission->value)[0])
        ->and($permission->description())->not->toBeEmpty();
})->with(Permission::cases());

it('marks compensation and access management as sensitive', function (): void {
    expect(Permission::CompensationView->isSensitive())->toBeTrue()
        ->and(Permission::AccessManage->isSensitive())->toBeTrue()
        ->and(Permission::EmployeeView->isSensitive())->toBeFalse();
});

it('ignores unknown permission keys when hydrating', function (): void {
    expect(Permission::fromValues(['employee.view', 'unknown.permission']))->toBe([Permission::EmployeeView]);
});

it('gives every system role a non-empty, duplicate-free permission set', function (SystemRole $role): void {
    $values = array_map(static fn (Permission $p): string => $p->value, $role->permissions());

    expect($values)->not->toBeEmpty()
        ->and(array_unique($values))->toHaveCount(count($values))
        ->and($role->displayName())->not->toBeEmpty();
})->with(SystemRole::cases());

it('keeps tenant administration separate from HR data access', function (): void {
    $hrDataModules = ['employee', 'leave', 'attendance', 'compensation', 'document'];
    $adminModules = array_map(static fn (Permission $p): string => $p->module(), SystemRole::TenantAdmin->permissions());

    expect(array_intersect($adminModules, $hrDataModules))->toBeEmpty();
});

it('never lets the auditor see sensitive data', function (): void {
    $sensitive = array_filter(SystemRole::Auditor->permissions(), static fn (Permission $p): bool => $p->isSensitive());

    expect($sensitive)->toBeEmpty();
});
