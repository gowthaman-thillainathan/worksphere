<?php

declare(strict_types=1);

use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Meridian\Modules\Identity\Http\RouteName;
use Meridian\Modules\Identity\Infrastructure\Persistence\Models\User;

const NEW_PASSWORD = 'a-brand-new-long-passphrase';

function resetPassword(array $overrides): Illuminate\Testing\TestResponse
{
    return test()->postJson(route(RouteName::ResetPassword->value), array_merge([
        'password' => NEW_PASSWORD,
        'password_confirmation' => NEW_PASSWORD,
    ], $overrides));
}

it('e-mails a reset link that opens the SPA', function (): void {
    Notification::fake();
    $user = User::factory()->create();

    $this->postJson(route(RouteName::ForgotPassword->value), ['email' => $user->email])->assertAccepted();

    Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user): bool {
        return str_starts_with(
            $notification->toMail($user)->actionUrl,
            'https://app.meridian.test/reset-password?token=',
        );
    });
});

it('answers the same way for unknown addresses and sends nothing', function (): void {
    Notification::fake();

    $this->postJson(route(RouteName::ForgotPassword->value), ['email' => 'nobody@meridian.test'])->assertAccepted();

    Notification::assertNothingSent();
});

it('does not send reset links to disabled accounts', function (): void {
    Notification::fake();
    $user = User::factory()->disabled()->create();

    $this->postJson(route(RouteName::ForgotPassword->value), ['email' => $user->email])->assertAccepted();

    Notification::assertNothingSent();
});

it('resets the password and signs the user out everywhere', function (): void {
    $user = User::factory()->unverified()->create();
    $session = issueTokensFor($user);

    resetPassword(['email' => $user->email, 'token' => Password::broker()->createToken($user)])->assertNoContent();

    $this->withToken($session->accessToken)->getJson(route(RouteName::CurrentUser->value))->assertUnauthorized();
    $this->postJson(route(RouteName::Login->value), ['email' => $user->email, 'password' => NEW_PASSWORD])->assertOk();

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('rejects an invalid reset token', function (): void {
    $user = User::factory()->create();

    resetPassword(['email' => $user->email, 'token' => 'invalid'])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'invalid_password_reset_token');
});

it('only accepts a reset token once', function (): void {
    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);

    resetPassword(['email' => $user->email, 'token' => $token])->assertNoContent();
    resetPassword(['email' => $user->email, 'token' => $token])->assertUnprocessable();
});

it('enforces the password policy', function (array $payload, string $field): void {
    $user = User::factory()->create();

    resetPassword(['email' => $user->email, 'token' => 'any', ...$payload])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonStructure(['errors' => [$field]]);
})->with([
    'too short' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
    'not confirmed' => [['password_confirmation' => 'something-else-entirely'], 'password'],
]);
