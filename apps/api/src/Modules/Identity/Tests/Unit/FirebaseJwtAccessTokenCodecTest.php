<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Firebase\JWT\JWT;
use Meridian\Modules\Identity\Domain\Exceptions\InvalidAccessToken;
use Meridian\Modules\Identity\Domain\ValueObjects\AccessTokenClaims;
use Meridian\Modules\Identity\Infrastructure\Security\FirebaseJwtAccessTokenCodec;

const TEST_JWT_SECRET = 'unit-test-secret-with-at-least-thirty-two-bytes-of-entropy';

function jwtCodec(array $overrides = []): FirebaseJwtAccessTokenCodec
{
    return new FirebaseJwtAccessTokenCodec(...array_merge([
        'secret' => TEST_JWT_SECRET,
        'algorithm' => 'HS256',
        'keyId' => 'v1',
        'issuer' => 'https://api.meridian.test',
        'audience' => 'meridian-api',
        'leewaySeconds' => 0,
    ], $overrides));
}

function accessClaims(int $ttlSeconds = 900): AccessTokenClaims
{
    $now = CarbonImmutable::now()->startOfSecond();

    return new AccessTokenClaims(
        subject: '0193a4c2-7f1e-7b3a-9c41-2d5e8f0a1b23',
        tokenId: '0193a4c2-7f1e-7b3a-9c41-000000000001',
        sessionId: '0193a4c2-7f1e-7b3a-9c41-000000000002',
        tokenVersion: 3,
        issuedAt: $now,
        expiresAt: $now->addSeconds($ttlSeconds),
    );
}

afterEach(fn () => CarbonImmutable::setTestNow());

it('round-trips every claim', function (): void {
    $claims = accessClaims();

    $decoded = jwtCodec()->decode(jwtCodec()->encode($claims));

    expect($decoded->subject)->toBe($claims->subject)
        ->and($decoded->tokenId)->toBe($claims->tokenId)
        ->and($decoded->sessionId)->toBe($claims->sessionId)
        ->and($decoded->tokenVersion)->toBe(3)
        ->and($decoded->issuedAt->getTimestamp())->toBe($claims->issuedAt->getTimestamp())
        ->and($decoded->expiresAt->getTimestamp())->toBe($claims->expiresAt->getTimestamp());
});

it('rejects an expired token', function (): void {
    $token = jwtCodec()->encode(accessClaims(ttlSeconds: 60));

    CarbonImmutable::setTestNow(CarbonImmutable::now()->addSeconds(61));

    jwtCodec()->decode($token);
})->throws(InvalidAccessToken::class);

it('tolerates clock skew within the configured leeway', function (): void {
    $token = jwtCodec()->encode(accessClaims(ttlSeconds: 60));

    CarbonImmutable::setTestNow(CarbonImmutable::now()->addSeconds(70));

    expect(jwtCodec(['leewaySeconds' => 30])->decode($token))->toBeInstanceOf(AccessTokenClaims::class);
});

it('rejects a token signed with another secret', function (): void {
    $token = jwtCodec(['secret' => str_repeat('x', 64)])->encode(accessClaims());

    jwtCodec()->decode($token);
})->throws(InvalidAccessToken::class);

it('rejects a token with a modified payload', function (): void {
    [$header, , $signature] = explode('.', jwtCodec()->encode(accessClaims()));
    $forgedPayload = JWT::urlsafeB64Encode((string) json_encode(['sub' => 'someone-else']));

    jwtCodec()->decode("{$header}.{$forgedPayload}.{$signature}");
})->throws(InvalidAccessToken::class);

it('rejects an unsigned "alg: none" token', function (): void {
    $header = JWT::urlsafeB64Encode((string) json_encode(['typ' => 'JWT', 'alg' => 'none', 'kid' => 'v1']));
    $payload = JWT::urlsafeB64Encode((string) json_encode(['sub' => 'attacker', 'exp' => time() + 900]));

    jwtCodec()->decode("{$header}.{$payload}.");
})->throws(InvalidAccessToken::class);

it('rejects a token from another issuer', function (): void {
    $token = jwtCodec(['issuer' => 'https://evil.example'])->encode(accessClaims());

    jwtCodec()->decode($token);
})->throws(InvalidAccessToken::class);

it('rejects a token for another audience', function (): void {
    $token = jwtCodec(['audience' => 'another-api'])->encode(accessClaims());

    jwtCodec()->decode($token);
})->throws(InvalidAccessToken::class);

it('rejects a token signed with a retired key id', function (): void {
    $token = jwtCodec(['keyId' => 'v0'])->encode(accessClaims());

    jwtCodec()->decode($token);
})->throws(InvalidAccessToken::class);

it('rejects malformed input', function (string $token): void {
    jwtCodec()->decode($token);
})->with(['', 'not-a-jwt', 'a.b', 'a.b.c.d'])->throws(InvalidAccessToken::class);

it('accepts base64-encoded secrets', function (): void {
    $codec = jwtCodec(['secret' => 'base64:'.base64_encode(TEST_JWT_SECRET)]);

    expect(jwtCodec()->decode($codec->encode(accessClaims())))->toBeInstanceOf(AccessTokenClaims::class);
});

it('refuses to start without a secret', function (): void {
    jwtCodec(['secret' => '']);
})->throws(InvalidArgumentException::class);
