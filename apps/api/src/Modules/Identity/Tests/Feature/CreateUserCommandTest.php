<?php

declare(strict_types=1);

use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailNotification;
use Illuminate\Support\Facades\Notification;
use Meridian\Modules\Access\Application\Actions\SyncPermissions;
use Meridian\Modules\Access\Contracts\RoleAssigner;
use Meridian\Modules\Identity\Infrastructure\Persistence\Models\User;

beforeEach(function (): void {
    Notification::fake();
    app(SyncPermissions::class)->handle();
});

function createUser(string $email, array $roles = [], string $password = 'a-long-enough-passphrase'): Illuminate\Testing\PendingCommand
{
    return test()->artisan('identity:create-user', ['email' => $email, '--name' => 'Ada Admin', '--role' => $roles])
        ->expectsQuestion('Password', $password);
}

it('creates a user with roles and sends the verification e-mail', function (): void {
    createUser('ada@meridian.test', ['tenant_admin'])->assertSuccessful();

    $user = User::query()->where('email', 'ada@meridian.test')->firstOrFail();

    expect($user->hasVerifiedEmail())->toBeFalse()
        ->and(app(RoleAssigner::class)->rolesOf($user->id))->toBe(['tenant_admin']);
    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

it('refuses duplicate e-mail addresses', function (): void {
    User::factory()->create(['email' => 'ada@meridian.test']);

    createUser('ADA@meridian.test')->assertFailed();
});

it('rolls back when a role does not exist', function (): void {
    createUser('ada@meridian.test', ['wizard'])->assertFailed();

    $this->assertDatabaseMissing('users', ['email' => 'ada@meridian.test']);
});

it('enforces the password policy', function (): void {
    createUser('ada@meridian.test', password: 'short')->assertExitCode(2);

    $this->assertDatabaseMissing('users', ['email' => 'ada@meridian.test']);
});
