<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Infrastructure\Security;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository as Cache;
use Meridian\Modules\Identity\Domain\Services\AccessTokenRevocationList;
use Meridian\Modules\Identity\Domain\ValueObjects\AccessTokenClaims;

/**
 * Stores revoked token ids in the cache (Redis in production) until the
 * token would have expired anyway, so the list never grows unbounded.
 */
final readonly class CacheAccessTokenRevocationList implements AccessTokenRevocationList
{
    private const CACHE_KEY = 'identity:revoked-access-token:%s';

    public function __construct(private Cache $cache) {}

    public function revoke(AccessTokenClaims $claims): void
    {
        $ttl = $claims->secondsUntilExpiry(CarbonImmutable::now());

        if ($ttl > 0) {
            $this->cache->put($this->key($claims->tokenId), true, $ttl);
        }
    }

    public function isRevoked(string $tokenId): bool
    {
        return $this->cache->has($this->key($tokenId));
    }

    private function key(string $tokenId): string
    {
        return sprintf(self::CACHE_KEY, $tokenId);
    }
}
