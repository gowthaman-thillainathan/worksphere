<?php

declare(strict_types=1);

use Meridian\Modules\Identity\Http\RouteName;
use Meridian\Modules\Identity\Infrastructure\Persistence\Models\RefreshToken;
use Meridian\Modules\Identity\Infrastructure\Persistence\Models\User;

function refreshWith(?string $plainToken): Illuminate\Testing\TestResponse
{
    $request = test();

    if ($plainToken !== null) {
        $request = $request->withCredentials()->withUnencryptedCookie(refreshCookieName(), $plainToken);
    }

    return $request->postJson(route(RouteName::Refresh->value));
}

it('rotates the refresh token and issues a new access token', function (): void {
    $tokens = issueTokensFor(User::factory()->create());

    $response = refreshWith($tokens->refreshToken->plain())->assertOk();
    $newCookie = $response->getCookie(refreshCookieName(), decrypt: false);

    expect($response->json('data.access_token'))->not->toBe($tokens->accessToken)
        ->and($newCookie->getValue())->not->toBe($tokens->refreshToken->plain());

    $previous = RefreshToken::query()->findOrFail($tokens->refreshTokenId);
    $current = RefreshToken::query()->findOrFail($previous->replaced_by_id);

    expect($previous->revoked_at)->not->toBeNull()
        ->and($current->family_id)->toBe($tokens->sessionId)
        ->and($current->revoked_at)->toBeNull();
});

it('revokes the whole session when a rotated token is replayed', function (): void {
    $tokens = issueTokensFor(User::factory()->create());

    $rotated = refreshWith($tokens->refreshToken->plain())->assertOk();
    $latest = $rotated->getCookie(refreshCookieName(), decrypt: false)->getValue();

    // An attacker replays the stolen, already-used token...
    refreshWith($tokens->refreshToken->plain())
        ->assertUnauthorized()
        ->assertJsonPath('code', 'invalid_refresh_token');

    // ...which also kills the legitimate user's newest token.
    refreshWith($latest)->assertUnauthorized();

    expect(RefreshToken::query()->where('family_id', $tokens->sessionId)->whereNull('revoked_at')->count())->toBe(0);
});

it('requires the refresh cookie', function (): void {
    refreshWith(null)->assertUnauthorized()->assertJsonPath('code', 'invalid_refresh_token');
});

it('rejects unknown refresh tokens', function (): void {
    refreshWith('forged-token')->assertUnauthorized();
});

it('rejects expired refresh tokens', function (): void {
    $tokens = issueTokensFor(User::factory()->create());

    $this->travel(config('identity.refresh_token.ttl_seconds') + 1)->seconds();

    refreshWith($tokens->refreshToken->plain())->assertUnauthorized();
});

it('refuses to refresh for a disabled account', function (): void {
    $user = User::factory()->create();
    $tokens = issueTokensFor($user);
    $user->forceFill(['status' => 'disabled'])->save();

    refreshWith($tokens->refreshToken->plain())->assertUnauthorized();
});
