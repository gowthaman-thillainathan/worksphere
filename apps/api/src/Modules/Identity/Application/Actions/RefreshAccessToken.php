<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Application\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Meridian\Modules\Identity\Application\Data\AuthenticatedSession;
use Meridian\Modules\Identity\Application\Data\ClientContext;
use Meridian\Modules\Identity\Domain\Events\RefreshTokenReuseDetected;
use Meridian\Modules\Identity\Domain\Exceptions\InvalidRefreshToken;
use Meridian\Modules\Identity\Domain\Repositories\RefreshTokenRepository;
use Meridian\Modules\Identity\Domain\Repositories\UserRepository;
use Meridian\Modules\Identity\Domain\ValueObjects\RefreshTokenSecret;
use Meridian\Modules\Identity\Infrastructure\Persistence\Models\RefreshToken;
use SensitiveParameter;

/**
 * Exchanges a refresh token for a new token pair (rotation).
 *
 * Each refresh token is single-use. Presenting one that was already rotated
 * means it was copied by someone else, so the entire session family is
 * revoked and the legitimate user must sign in again (OAuth 2.0 Security BCP
 * §4.14 "refresh token reuse detection").
 */
final readonly class RefreshAccessToken
{
    public function __construct(
        private RefreshTokenRepository $refreshTokens,
        private UserRepository $users,
        private IssueTokenPair $issueTokenPair,
        private ConnectionInterface $database,
        private Dispatcher $events,
    ) {}

    /**
     * @throws InvalidRefreshToken
     */
    public function handle(#[SensitiveParameter] string $plainRefreshToken, ClientContext $client): AuthenticatedSession
    {
        $tokenHash = RefreshTokenSecret::fromPlain($plainRefreshToken)->hash();
        $token = $this->refreshTokens->findByHash($tokenHash) ?? throw new InvalidRefreshToken;

        if ($token->revoked_at !== null) {
            $this->revokeCompromisedFamily($token, $client);
        }

        return $this->database->transaction(fn (): AuthenticatedSession => $this->rotate($tokenHash, $client));
    }

    private function rotate(string $tokenHash, ClientContext $client): AuthenticatedSession
    {
        $now = CarbonImmutable::now();

        // Re-read under a row lock: a concurrent refresh may have rotated it meanwhile.
        $token = $this->refreshTokens->findByHashForUpdate($tokenHash);

        if ($token === null || ! $token->isUsableAt($now)) {
            throw new InvalidRefreshToken;
        }

        $user = $this->users->findById($token->user_id);

        if ($user === null || ! $user->canAuthenticateAt($now)) {
            $this->refreshTokens->revokeFamily($token->family_id, $now);

            throw new InvalidRefreshToken;
        }

        $tokens = $this->issueTokenPair->handle($user, $client, $token->family_id);
        $this->refreshTokens->markReplaced($token, $tokens->refreshTokenId, $now);

        return new AuthenticatedSession($user, $tokens);
    }

    /**
     * @throws InvalidRefreshToken always
     */
    private function revokeCompromisedFamily(RefreshToken $token, ClientContext $client): never
    {
        $this->refreshTokens->revokeFamily($token->family_id, CarbonImmutable::now());
        $this->events->dispatch(new RefreshTokenReuseDetected($token->user_id, $token->family_id, $client->ipAddress));

        throw new InvalidRefreshToken;
    }
}
