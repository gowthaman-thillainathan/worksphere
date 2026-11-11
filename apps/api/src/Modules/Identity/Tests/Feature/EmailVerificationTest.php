<?php

declare(strict_types=1);

use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailNotification;
use Illuminate\Support\Facades\Notification;
use Meridian\Modules\Identity\Http\RouteName;
use Meridian\Modules\Identity\Infrastructure\Persistence\Models\User;

function verificationLinkFor(User $user): string
{
    Notification::fake();
    $user->sendEmailVerificationNotification();

    $link = '';
    Notification::assertSentTo($user, VerifyEmailNotification::class, function (VerifyEmailNotification $notification) use ($user, &$link): bool {
        $link = $notification->toMail($user)->actionUrl;

        return true;
    });

    return $link;
}

it('verifies the address from the signed link and redirects to the SPA', function (): void {
    $user = User::factory()->unverified()->create();

    $this->get(verificationLinkFor($user))->assertRedirect('https://app.meridian.test/verify-email?status=verified');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('rejects a link with a tampered signature', function (): void {
    $user = User::factory()->unverified()->create();

    $this->get(verificationLinkFor($user).'0')->assertRedirect('https://app.meridian.test/verify-email?status=failed');

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('rejects an expired link', function (): void {
    $user = User::factory()->unverified()->create();
    $link = verificationLinkFor($user);

    $this->travel(config('identity.email_verification.expire_minutes') + 1)->minutes();

    $this->get($link)->assertRedirect('https://app.meridian.test/verify-email?status=failed');
});

it('rejects a link issued for a previous e-mail address', function (): void {
    $user = User::factory()->unverified()->create();
    $link = verificationLinkFor($user);
    $user->forceFill(['email' => 'changed@meridian.test'])->save();

    $this->get($link)->assertRedirect('https://app.meridian.test/verify-email?status=failed');
});

it('re-sends the verification link on request', function (): void {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->withToken(issueTokensFor($user)->accessToken)
        ->postJson(route(RouteName::ResendEmailVerification->value))
        ->assertAccepted();

    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

it('does not re-send to verified users', function (): void {
    Notification::fake();
    $user = User::factory()->create();

    $this->withToken(issueTokensFor($user)->accessToken)
        ->postJson(route(RouteName::ResendEmailVerification->value))
        ->assertAccepted();

    Notification::assertNothingSent();
});
