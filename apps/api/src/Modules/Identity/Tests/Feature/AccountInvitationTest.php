<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Notification;
use Meridian\Modules\Identity\Contracts\AccountProvisioner;
use Meridian\Modules\Identity\Http\RouteName;
use Meridian\Modules\Identity\Infrastructure\Notifications\AccountInvitationNotification;
use Meridian\Modules\Identity\Infrastructure\Persistence\Models\User;

beforeEach(function (): void {
    app(Meridian\Modules\Access\Application\Actions\SyncPermissions::class)->handle();
    Notification::fake();
});

it('invites a passwordless account that cannot sign in yet', function (): void {
    $account = app(AccountProvisioner::class)->invite('Ishara Fernando', 'ishara@meridian.test', 'auditor');

    expect($account->roles)->toBe(['auditor'])
        ->and($account->emailVerified)->toBeFalse();

    $this->postJson(route(RouteName::Login->value), ['email' => 'ishara@meridian.test', 'password' => 'anything-at-all-123'])
        ->assertUnauthorized();
});

it('e-mails a set-password link that also verifies the address', function (): void {
    app(AccountProvisioner::class)->invite('Ishara Fernando', 'ishara@meridian.test', 'auditor');
    $user = User::query()->where('email', 'ishara@meridian.test')->sole();
    $token = null;

    Notification::assertSentTo($user, AccountInvitationNotification::class, function (AccountInvitationNotification $notification) use ($user, &$token): bool {
        $token = $notification->token;

        return str_starts_with($notification->toMail($user)->actionUrl, 'https://app.meridian.test/reset-password?token=')
            && str_contains($notification->toMail($user)->actionUrl, 'invited=1');
    });

    $this->postJson(route(RouteName::ResetPassword->value), [
        'token' => $token,
        'email' => 'ishara@meridian.test',
        'password' => 'a-brand-new-long-passphrase',
        'password_confirmation' => 'a-brand-new-long-passphrase',
    ])->assertNoContent();

    expect($user->fresh()->email_verified_at)->not->toBeNull();
});

it('replaces the role when it changes', function (): void {
    $account = app(AccountProvisioner::class)->invite('Ishara Fernando', 'ishara@meridian.test', 'auditor');

    expect(app(AccountProvisioner::class)->changeRole($account->id, 'payroll_officer')->roles)->toBe(['payroll_officer']);
});
