<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Application\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Meridian\Modules\Identity\Application\Data\ClientContext;
use Meridian\Modules\Identity\Application\Data\TokenPair;
use Meridian\Modules\Identity\Application\Data\TokenPolicy;
use Meridian\Modules\Identity\Domain\Repositories\RefreshTokenRepository;
use Meridian\Modules\Identity\Domain\Services\AccessTokenCodec;
use Meridian\Modules\Identity\Domain\ValueObjects\AccessTokenClaims;
use Meridian\Modules\Identity\Domain\ValueObjects\RefreshTokenSecret;
use Meridian\Modules\Identity\Infrastructure\Persistence\Models\User;

/**
 * Issues an access token plus a refresh token. A new login starts a new
 * session family; a refresh continues the existing one.
 *
 * Must run inside the caller's transaction.
 */
final readonly class IssueTokenPair
{
    public function __construct(
        private AccessTokenCodec $codec,
        private RefreshTokenRepository $refreshTokens,
        private TokenPolicy $policy,
    ) {}

    public function handle(User $user, ClientContext $client, ?string $sessionId = null): TokenPair
    {
        $now = CarbonImmutable::now();
        $sessionId ??= (string) Str::uuid7();

        $refreshSecret = RefreshTokenSecret::generate($this->policy->refreshTokenEntropyBytes);
        $refreshExpiresAt = $now->addSeconds($this->policy->refreshTokenTtlSeconds);

        $refreshToken = $this->refreshTokens->create(
            userId: $user->id,
            familyId: $sessionId,
            tokenHash: $refreshSecret->hash(),
            expiresAt: $refreshExpiresAt,
            ipAddress: $client->ipAddress,
            userAgent: $client->userAgent,
        );

        $claims = new AccessTokenClaims(
            subject: $user->id,
            tokenId: (string) Str::uuid7(),
            sessionId: $sessionId,
            tokenVersion: $user->token_version,
            issuedAt: $now,
            expiresAt: $now->addSeconds($this->policy->accessTokenTtlSeconds),
        );

        return new TokenPair(
            accessToken: $this->codec->encode($claims),
            accessTokenExpiresAt: $claims->expiresAt,
            refreshToken: $refreshSecret,
            refreshTokenExpiresAt: $refreshExpiresAt,
            sessionId: $sessionId,
            refreshTokenId: $refreshToken->id,
        );
    }
}
