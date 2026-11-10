<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Infrastructure\Security;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Meridian\Modules\Identity\Domain\Exceptions\InvalidAccessToken;
use Meridian\Modules\Identity\Domain\Repositories\UserRepository;
use Meridian\Modules\Identity\Domain\Services\AccessTokenCodec;
use Meridian\Modules\Identity\Domain\Services\AccessTokenRevocationList;
use Meridian\Modules\Identity\Domain\ValueObjects\AccessTokenClaims;
use Meridian\Modules\Identity\Infrastructure\Persistence\Models\User;
use Meridian\Shared\Infrastructure\Logging\ContextKey;

/**
 * Resolves the user behind a "Authorization: Bearer <jwt>" header for the
 * "jwt" guard. Returns null (→ 401) for any token that is malformed, expired,
 * revoked, superseded by a newer token version, or whose user can no longer
 * sign in. The reason is never disclosed to the client.
 */
final readonly class BearerTokenAuthenticator
{
    /** Guard name, as configured in config/auth.php. */
    public const GUARD = 'api';

    /** Guard driver registered by the Identity module. */
    public const DRIVER = 'jwt';

    public function __construct(
        private AccessTokenCodec $codec,
        private AccessTokenRevocationList $revocations,
        private UserRepository $users,
    ) {}

    public function __invoke(Request $request): ?User
    {
        $token = $request->bearerToken();

        if ($token === null || $token === '') {
            return null;
        }

        try {
            $claims = $this->codec->decode($token);
        } catch (InvalidAccessToken) {
            return null;
        }

        $user = $this->users->findById($claims->subject);

        if (! $this->isAcceptable($claims, $user)) {
            return null;
        }

        $request->attributes->set(AccessTokenClaims::REQUEST_ATTRIBUTE, $claims);
        Context::add(ContextKey::UserId->value, $user->id);

        return $user;
    }

    /**
     * @phpstan-assert-if-true User $user
     */
    private function isAcceptable(AccessTokenClaims $claims, ?User $user): bool
    {
        return $user !== null
            && $user->canAuthenticateAt(CarbonImmutable::now())
            && $claims->tokenVersion === $user->token_version
            && ! $this->revocations->isRevoked($claims->tokenId);
    }
}
