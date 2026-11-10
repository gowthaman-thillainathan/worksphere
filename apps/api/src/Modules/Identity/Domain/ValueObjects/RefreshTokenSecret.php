<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Domain\ValueObjects;

use SensitiveParameter;

/**
 * An opaque refresh token. Only its SHA-256 hash is ever persisted, so a
 * database leak does not expose usable tokens.
 */
final readonly class RefreshTokenSecret
{
    private const HASH_ALGORITHM = 'sha256';

    private function __construct(
        #[SensitiveParameter]
        private string $plain,
    ) {}

    public static function generate(int $entropyBytes): self
    {
        return new self(rtrim(strtr(base64_encode(random_bytes($entropyBytes)), '+/', '-_'), '='));
    }

    public static function fromPlain(#[SensitiveParameter] string $plain): self
    {
        return new self($plain);
    }

    public function plain(): string
    {
        return $this->plain;
    }

    public function hash(): string
    {
        return hash(self::HASH_ALGORITHM, $this->plain);
    }
}
