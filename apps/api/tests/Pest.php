<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Meridian\Modules\Access\Application\Actions\SyncPermissions;
use Meridian\Modules\Access\Contracts\RoleAssigner;
use Meridian\Modules\Identity\Application\Actions\IssueTokenPair;
use Meridian\Modules\Identity\Application\Data\ClientContext;
use Meridian\Modules\Identity\Application\Data\TokenPair;
use Meridian\Modules\Identity\Infrastructure\Persistence\Models\User;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test case bindings
|--------------------------------------------------------------------------
|
| Unit tests are plain PHPUnit tests: no framework, no database.
| Feature tests boot the application against PostgreSQL (never SQLite;
| see docs/10 §1) inside a transaction per test.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', '../src/Modules/*/Tests/Feature');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * Issues a real token pair for the user without going through the login endpoint.
 */
function issueTokensFor(User $user): TokenPair
{
    return app(IssueTokenPair::class)->handle($user, new ClientContext('127.0.0.1', 'Pest'));
}

function refreshCookieName(): string
{
    return (string) config('identity.refresh_token.cookie.name');
}

/**
 * A verified user holding the given system role (or none), ready to call the API.
 */
function userWithRole(?string $role): User
{
    app(SyncPermissions::class)->handle();
    $user = User::factory()->create();

    if ($role !== null) {
        app(RoleAssigner::class)->assign($user->id, $role);
    }

    return $user;
}

function tokenForRole(?string $role): string
{
    return issueTokensFor(userWithRole($role))->accessToken;
}
