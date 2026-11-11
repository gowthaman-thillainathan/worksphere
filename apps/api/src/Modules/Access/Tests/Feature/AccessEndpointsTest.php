<?php

declare(strict_types=1);

use Meridian\Modules\Access\Contracts\Permission;
use Meridian\Modules\Access\Contracts\RoleAssigner;
use Meridian\Modules\Identity\Infrastructure\Persistence\Models\User;

function accessUser(?string $role, bool $verified = true): User
{
    $user = userWithRole($role);

    if (! $verified) {
        $user->forceFill(['email_verified_at' => null])->save();
    }

    return $user;
}

it('lists the permission catalogue for access managers', function (): void {
    $this->withToken(issueTokensFor(accessUser('tenant_admin'))->accessToken)
        ->getJson(route('access.permissions.index'))
        ->assertOk()
        ->assertJsonCount(count(Permission::cases()), 'data')
        ->assertJsonFragment(['key' => 'compensation.view', 'module' => 'compensation', 'is_sensitive' => true]);
});

it('lists roles with their permissions', function (): void {
    $this->withToken(issueTokensFor(accessUser('tenant_admin'))->accessToken)
        ->getJson(route('access.roles.index'))
        ->assertOk()
        ->assertJsonCount(4, 'data')
        ->assertJsonPath('data.0.key', 'auditor')
        ->assertJsonPath('data.0.is_system', true);
});

it('forbids users without the access.manage permission', function (?string $role): void {
    $this->withToken(issueTokensFor(accessUser($role))->accessToken)
        ->getJson(route('access.permissions.index'))
        ->assertForbidden()
        ->assertJsonPath('code', 'forbidden');
})->with(['no role' => [null], 'hr admin' => ['hr_admin'], 'auditor' => ['auditor']]);

it('requires a verified e-mail address before any permission is honoured', function (): void {
    $this->withToken(issueTokensFor(accessUser('tenant_admin', verified: false))->accessToken)
        ->getJson(route('access.permissions.index'))
        ->assertForbidden()
        ->assertJsonPath('code', 'email_not_verified');
});

it('requires authentication', function (): void {
    $this->getJson(route('access.roles.index'))->assertUnauthorized();
});

it('applies a newly granted role on the next request', function (): void {
    $user = accessUser(null);
    $token = issueTokensFor($user)->accessToken;

    $this->withToken($token)->getJson(route('access.roles.index'))->assertForbidden();

    app(RoleAssigner::class)->assign($user->id, 'tenant_admin');

    $this->withToken($token)->getJson(route('access.roles.index'))->assertOk();
});

it('ignores role assignments that have expired', function (): void {
    $user = accessUser('tenant_admin');
    Meridian\Modules\Access\Infrastructure\Persistence\Models\RoleAssignment::query()
        ->where('user_id', $user->id)
        ->update(['expires_at' => now()->subMinute()]);

    $this->withToken(issueTokensFor($user)->accessToken)
        ->getJson(route('access.roles.index'))
        ->assertForbidden();
});
