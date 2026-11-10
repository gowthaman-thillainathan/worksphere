<?php

declare(strict_types=1);

use Meridian\Modules\Identity\Http\RouteName;
use Meridian\Modules\Identity\Infrastructure\Persistence\Models\User;

it('ends the current session immediately', function (): void {
    $tokens = issueTokensFor(User::factory()->create());

    $response = $this->withToken($tokens->accessToken)->postJson(route(RouteName::Logout->value))->assertNoContent();

    expect($response->getCookie(refreshCookieName(), decrypt: false)->isCleared())->toBeTrue();

    $this->withToken($tokens->accessToken)->getJson(route(RouteName::CurrentUser->value))->assertUnauthorized();

    $this->withCredentials()->withUnencryptedCookie(refreshCookieName(), $tokens->refreshToken->plain())
        ->postJson(route(RouteName::Refresh->value))
        ->assertUnauthorized();
});

it('keeps other sessions alive on a normal logout', function (): void {
    $user = User::factory()->create();
    $laptop = issueTokensFor($user);
    $phone = issueTokensFor($user);

    $this->withToken($laptop->accessToken)->postJson(route(RouteName::Logout->value))->assertNoContent();

    $this->withToken($phone->accessToken)->getJson(route(RouteName::CurrentUser->value))->assertOk();
});

it('ends every session on logout-all', function (): void {
    $user = User::factory()->create();
    $laptop = issueTokensFor($user);
    $phone = issueTokensFor($user);

    $this->withToken($laptop->accessToken)->postJson(route(RouteName::LogoutEverywhere->value))->assertNoContent();

    $this->withToken($phone->accessToken)->getJson(route(RouteName::CurrentUser->value))->assertUnauthorized();
    $this->withCredentials()->withUnencryptedCookie(refreshCookieName(), $phone->refreshToken->plain())
        ->postJson(route(RouteName::Refresh->value))
        ->assertUnauthorized();
});

it('requires authentication to log out', function (): void {
    $this->postJson(route(RouteName::Logout->value))->assertUnauthorized();
});
