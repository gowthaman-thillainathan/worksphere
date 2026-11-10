<?php

declare(strict_types=1);

use Meridian\Modules\Access\Application\Actions\SyncPermissions;
use Meridian\Modules\Access\Contracts\Permission;
use Meridian\Modules\Access\Contracts\RoleAssigner;
use Meridian\Modules\Identity\Infrastructure\Persistence\Models\User;

beforeEach(fn () => app(SyncPermissions::class)->handle());

function userWithRole(?string $role, bool $verified = true): User
{
    $factory = User::factory();
    $user = ($verified ? $factory : $factory->unverified())->create();

    if ($role !== null) {
        app(RoleAssigner::class)->assign($user->id, $role);
    }

    return $user;
}

it('lists the permission catalogue for access managers', function (): void {
    $this->withToken(issueTokensFor(userWithRole('tenant_admin'))->accessToken)
        ->getJson(route('access.permissions.index'))
        ->assertOk()
        ->assertJsonCount(count(Permission::cases()), 'data')
        ->assertJsonFragment(['key' => 'compensation.view', 'module' => 'compensation', 'is_sensitive' => true]);
});

it('lists roles with their permissions', function (): void {
    $this->withToken(issueTokensFor(userWithRole('tenant_admin'))->accessToken)
        ->getJson(route('access.roles.index'))
        ->assertOk()
        ->assertJsonCount(4, 'data')
        ->assertJsonPath('data.0.key', 'auditor')
        ->assertJsonPath('data.0.is_system', true);
});

it('forbids users without the access.manage permission', function (?string $role): void {
    $this->withToken(issueTokensFor(userWithRole($role))->accessToken)
        ->getJson(route('access.permissions.index'))
        ->assertForbidden()
        ->assertJsonPath('code', 'forbidden');
})->with(['no role' => [null], 'hr admin' => ['hr_admin'], 'auditor' => ['auditor']]);

it('requires a verified e-mail address before any permission is honoured', function (): void {
    $this->withToken(issueTokensFor(userWithRole('tenant_admin', verified: false))->accessToken)
        ->getJson(route('access.permissions.index'))
        ->assertForbidden()
        ->assertJsonPath('code', 'email_not_verified');
});

it('requires authentication', function (): void {
    $this->getJson(route('access.roles.index'))->assertUnauthorized();
});

it('applies a newly granted role on the next request', function (): void {
    $user = userWithRole(null);
    $token = issueTokensFor($user)->accessToken;

    $this->withToken($token)->getJson(route('access.roles.index'))->assertForbidden();

    app(RoleAssigner::class)->assign($user->id, 'tenant_admin');

    $this->withToken($token)->getJson(route('access.roles.index'))->assertOk();
});

it('ignores role assignments that have expired', function (): void {
    $user = userWithRole('tenant_admin');
    Meridian\Modules\Access\Infrastructure\Persistence\Models\RoleAssignment::query()
        ->where('user_id', $user->id)
        ->update(['expires_at' => now()->subMinute()]);

    $this->withToken(issueTokensFor($user)->accessToken)
        ->getJson(route('access.roles.index'))
        ->assertForbidden();
});
