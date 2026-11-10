<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;
use Meridian\Modules\Identity\Database\Factories\UserFactory;
use Meridian\Modules\Identity\Http\RouteName;
use Meridian\Modules\Identity\Infrastructure\Notifications\AccountLockedNotification;
use Meridian\Modules\Identity\Infrastructure\Persistence\Models\User;

function login(string $email, string $password = UserFactory::PASSWORD): Illuminate\Testing\TestResponse
{
    return test()->postJson(route(RouteName::Login->value), ['email' => $email, 'password' => $password]);
}

it('returns an access token and sets the refresh token as a hardened cookie', function (): void {
    $user = User::factory()->create();

    $response = login(strtoupper($user->email));

    $response->assertOk()
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.expires_in', 900)
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonMissingPath('data.refresh_token')
        ->assertJsonStructure(['data' => ['access_token', 'expires_at', 'user' => ['email', 'email_verified', 'status']]]);

    $cookie = $response->getCookie(refreshCookieName(), decrypt: false);

    expect($cookie)->not->toBeNull()
        ->and($cookie->isHttpOnly())->toBeTrue()
        ->and($cookie->isSecure())->toBeTrue()
        ->and($cookie->getSameSite())->toBe('strict')
        ->and($cookie->getPath())->toBe('/api/v1/auth')
        ->and($user->fresh()->last_login_at)->not->toBeNull();

    $this->assertDatabaseCount('refresh_tokens', 1);
});

it('answers identically for a wrong password and an unknown account', function (): void {
    $user = User::factory()->create();

    $wrongPassword = login($user->email, 'not-the-password');
    $unknownAccount = login('nobody@meridian.test');

    foreach ([$wrongPassword, $unknownAccount] as $response) {
        $response->assertUnauthorized()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('code', 'invalid_credentials')
            ->assertJsonPath('detail', 'The provided credentials are incorrect.');
    }
});

it('rejects disabled accounts even with the right password', function (): void {
    $user = User::factory()->disabled()->create();

    login($user->email)->assertUnauthorized()->assertJsonPath('code', 'invalid_credentials');
});

it('locks the account after repeated failures and tells the owner', function (): void {
    Notification::fake();
    $user = User::factory()->create();

    foreach (range(1, config('identity.lockout.max_attempts')) as $attempt) {
        login($user->email, 'wrong-password-'.$attempt)->assertUnauthorized();
    }

    expect($user->fresh()->locked_until)->not->toBeNull();
    Notification::assertSentTo($user, AccountLockedNotification::class);

    // Past the per-minute rate limit but still inside the lockout window.
    $this->travel(2)->minutes();
    login($user->email)->assertUnauthorized();

    $this->travel(config('identity.lockout.duration_seconds'))->seconds();
    login($user->email)->assertOk();
});

it('resets the failure counter after a successful login', function (): void {
    $user = User::factory()->create();

    login($user->email, 'wrong-password')->assertUnauthorized();
    login($user->email)->assertOk();

    expect($user->fresh()->failed_login_count)->toBe(0);
});

it('validates the request body', function (): void {
    $this->postJson(route(RouteName::Login->value), ['email' => 'not-an-email'])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonPath('errors.email.0.code', 'email')
        ->assertJsonPath('errors.password.0.code', 'required');
});

it('rate limits attempts per account', function (): void {
    foreach (range(1, config('identity.rate_limits.login_per_account')) as $ignored) {
        login('target@meridian.test', 'guess')->assertUnauthorized();
    }

    login('target@meridian.test', 'guess')
        ->assertTooManyRequests()
        ->assertJsonPath('code', 'rate_limited')
        ->assertHeader('Retry-After');
});

it('upgrades outdated password hashes on login', function (): void {
    $user = User::factory()->create();
    // Simulate a hash created under an older cost setting (bypasses the "hashed" cast).
    User::query()->whereKey($user->id)->update(['password' => password_hash(UserFactory::PASSWORD, PASSWORD_BCRYPT, ['cost' => 5])]);

    login($user->email)->assertOk();

    expect(password_get_info($user->fresh()->password)['options']['cost'])->toBe((int) config('hashing.bcrypt.rounds'));
});

it('accepts a locked account once the lock has expired', function (): void {
    $user = User::factory()->lockedUntil(CarbonImmutable::now()->subMinute())->create();

    login($user->email)->assertOk();
});
