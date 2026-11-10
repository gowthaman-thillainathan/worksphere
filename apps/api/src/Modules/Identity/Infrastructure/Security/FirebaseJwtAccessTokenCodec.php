<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Infrastructure\Security;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use InvalidArgumentException;
use Meridian\Modules\Identity\Domain\Exceptions\InvalidAccessToken;
use Meridian\Modules\Identity\Domain\Services\AccessTokenCodec;
use Meridian\Modules\Identity\Domain\ValueObjects\AccessTokenClaims;
use SensitiveParameter;
use stdClass;
use Throwable;

/**
 * HMAC-signed JWT access tokens (firebase/php-jwt).
 *
 * The verifying key is pinned to one algorithm, so "alg: none" and algorithm
 * confusion attacks are rejected by construction. The "kid" header enables
 * key rotation; switching to an asymmetric algorithm only touches this class.
 */
final readonly class FirebaseJwtAccessTokenCodec implements AccessTokenCodec
{
    private const BASE64_PREFIX = 'base64:';

    private const REQUIRED_CLAIMS = ['iss', 'aud', 'sub', 'jti', 'sid', 'ver', 'iat', 'nbf', 'exp'];

    private const TOKEN_TYPE = 'JWT';

    private string $secret;

    public function __construct(
        #[SensitiveParameter]
        string $secret,
        private string $algorithm,
        private string $keyId,
        private string $issuer,
        private string $audience,
        private int $leewaySeconds,
    ) {
        $this->secret = self::decodeSecret($secret);
    }

    public function encode(AccessTokenClaims $claims): string
    {
        return JWT::encode([
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'sub' => $claims->subject,
            'jti' => $claims->tokenId,
            'sid' => $claims->sessionId,
            'ver' => $claims->tokenVersion,
            'iat' => $claims->issuedAt->getTimestamp(),
            'nbf' => $claims->issuedAt->getTimestamp(),
            'exp' => $claims->expiresAt->getTimestamp(),
        ], $this->secret, $this->algorithm, $this->keyId);
    }

    public function decode(string $token): AccessTokenClaims
    {
        $headers = new stdClass;

        // The library reads a static clock; align it with the application clock
        // (which tests can freeze) for the duration of this call only.
        JWT::$timestamp = CarbonImmutable::now()->getTimestamp();
        JWT::$leeway = $this->leewaySeconds;

        try {
            $payload = JWT::decode($token, new Key($this->secret, $this->algorithm), $headers);
        } catch (Throwable $exception) {
            throw InvalidAccessToken::because('signature or time claims rejected', $exception);
        } finally {
            JWT::$timestamp = null;
            JWT::$leeway = 0;
        }

        $this->assertHeaders($headers);
        $this->assertClaims($payload);

        return new AccessTokenClaims(
            subject: (string) $payload->sub,
            tokenId: (string) $payload->jti,
            sessionId: (string) $payload->sid,
            tokenVersion: (int) $payload->ver,
            issuedAt: (new DateTimeImmutable)->setTimestamp((int) $payload->iat),
            expiresAt: (new DateTimeImmutable)->setTimestamp((int) $payload->exp),
        );
    }

    private function assertHeaders(stdClass $headers): void
    {
        if (($headers->kid ?? null) !== $this->keyId) {
            throw InvalidAccessToken::because('unknown key id');
        }

        if (($headers->typ ?? null) !== self::TOKEN_TYPE) {
            throw InvalidAccessToken::because('unexpected token type');
        }
    }

    private function assertClaims(stdClass $payload): void
    {
        foreach (self::REQUIRED_CLAIMS as $claim) {
            if (! property_exists($payload, $claim)) {
                throw InvalidAccessToken::because(sprintf('missing "%s" claim', $claim));
            }
        }

        if ($payload->iss !== $this->issuer) {
            throw InvalidAccessToken::because('unexpected issuer');
        }

        if ($payload->aud !== $this->audience) {
            throw InvalidAccessToken::because('unexpected audience');
        }
    }

    private static function decodeSecret(string $secret): string
    {
        $decoded = str_starts_with($secret, self::BASE64_PREFIX)
            ? base64_decode(substr($secret, strlen(self::BASE64_PREFIX)), true)
            : $secret;

        if ($decoded === false || $decoded === '') {
            throw new InvalidArgumentException('JWT_SECRET is missing or malformed.');
        }

        return $decoded;
    }
}
