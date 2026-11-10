<?php

declare(strict_types=1);

use Meridian\Modules\Access\Application\Actions\SyncPermissions;
use Meridian\Modules\Access\Contracts\RoleAssigner;
use Meridian\Modules\Identity\Domain\ValueObjects\UserStatus;
use Meridian\Modules\Identity\Http\RouteName;
use Meridian\Modules\Identity\Infrastructure\Persistence\Models\User;

it('authenticates requests that carry a valid bearer token', function (): void {
    app(SyncPermissions::class)->handle();
    $user = User::factory()->create();
    app(RoleAssigner::class)->assign($user->id, 'auditor');

    $this->withToken(issueTokensFor($user)->accessToken)
        ->getJson(route(RouteName::CurrentUser->value))
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('meta.roles', ['auditor'])
        ->assertJsonFragment(['audit.view']);
});

it('returns a problem response without a token', function (): void {
    $this->getJson(route(RouteName::CurrentUser->value))
        ->assertUnauthorized()
        ->assertHeader('WWW-Authenticate', 'Bearer')
        ->assertJsonPath('code', 'unauthenticated');
});

it('rejects an expired access token', function (): void {
    $token = issueTokensFor(User::factory()->create())->accessToken;

    $this->travel(config('identity.jwt.ttl_seconds') + config('identity.jwt.leeway_seconds') + 1)->seconds();

    $this->withToken($token)->getJson(route(RouteName::CurrentUser->value))->assertUnauthorized();
});

it('rejects a tampered access token', function (): void {
    $token = issueTokensFor(User::factory()->create())->accessToken;

    $this->withToken($token.'x')->getJson(route(RouteName::CurrentUser->value))->assertUnauthorized();
});

it('stops accepting tokens of an account that was disabled', function (): void {
    $user = User::factory()->create();
    $token = issueTokensFor($user)->accessToken;

    $user->forceFill(['status' => UserStatus::Disabled])->save();

    $this->withToken($token)->getJson(route(RouteName::CurrentUser->value))->assertUnauthorized();
});

it('lets unverified users reach /me so the SPA can prompt for verification', function (): void {
    $user = User::factory()->unverified()->create();

    $this->withToken(issueTokensFor($user)->accessToken)
        ->getJson(route(RouteName::CurrentUser->value))
        ->assertOk()
        ->assertJsonPath('data.email_verified', false);
});
