<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Meridian\Modules\Identity\Application\Data\ClientContext;
use Meridian\Modules\Identity\Application\Data\TokenPair;
use Meridian\Modules\Identity\Domain\ValueObjects\AccessTokenClaims;
use Meridian\Modules\Identity\Domain\ValueObjects\EmailVerificationHash;
use Meridian\Modules\Identity\Domain\ValueObjects\RefreshTokenSecret;
use Meridian\Modules\Identity\Domain\ValueObjects\UserStatus;

it('only lets active accounts authenticate', function (UserStatus $status, bool $expected): void {
    expect($status->canAuthenticate())->toBe($expected);
})->with([
    'active' => [UserStatus::Active, true],
    'disabled' => [UserStatus::Disabled, false],
]);

it('binds a verification hash to one e-mail address', function (): void {
    $hash = EmailVerificationHash::of('ada@meridian.test');

    expect(EmailVerificationHash::matches('ada@meridian.test', $hash))->toBeTrue()
        ->and(EmailVerificationHash::matches('eve@meridian.test', $hash))->toBeFalse();
});

it('truncates oversized user agents', function (): void {
    $client = new ClientContext('10.0.0.1', str_repeat('a', 2000));

    expect($client->userAgent)->toHaveLength(512);
});

it('reports the remaining lifetime of tokens without going negative', function (): void {
    $now = CarbonImmutable::parse('2026-11-10 08:00:00');
    $claims = new AccessTokenClaims('user', 'jti', 'sid', 0, $now, $now->addMinutes(15));
    $pair = new TokenPair('jwt', $now->addMinutes(15), RefreshTokenSecret::fromPlain('r'), $now->addDays(14), 'sid', 'rid');

    expect($claims->secondsUntilExpiry($now))->toBe(900)
        ->and($claims->secondsUntilExpiry($now->addHour()))->toBe(0)
        ->and($pair->accessTokenLifetime($now->addMinutes(5)))->toBe(600);
});
