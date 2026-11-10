<?php

declare(strict_types=1);

namespace Meridian\Modules\Access\Infrastructure\Authorization;

use Illuminate\Contracts\Cache\Repository as Cache;
use Meridian\Modules\Access\Contracts\Authorizer;
use Meridian\Modules\Access\Contracts\Permission;
use Meridian\Modules\Access\Domain\Repositories\PermissionRepository;

/**
 * Resolves a user's effective permissions from their active role assignments
 * and caches the compiled set. The cache is invalidated explicitly whenever
 * assignments change and otherwise expires after a short TTL, which also
 * bounds how long an expired assignment can linger.
 */
final class CachedRoleBasedAuthorizer implements Authorizer
{
    private const CACHE_KEY = 'access:user:%s:permissions';

    /**
     * Per-request memo so one request never hits the cache twice for a user.
     *
     * @var array<string, list<Permission>>
     */
    private array $resolved = [];

    public function __construct(
        private readonly PermissionRepository $permissions,
        private readonly Cache $cache,
        private readonly int $ttlSeconds,
    ) {}

    public function allows(string $userId, Permission $permission): bool
    {
        return in_array($permission, $this->permissionsFor($userId), true);
    }

    public function permissionsFor(string $userId): array
    {
        return $this->resolved[$userId] ??= Permission::fromValues(
            $this->cache->remember(
                $this->cacheKey($userId),
                $this->ttlSeconds,
                fn (): array => $this->permissions->keysGrantedTo($userId),
            ),
        );
    }

    public function forget(string $userId): void
    {
        unset($this->resolved[$userId]);
        $this->cache->forget($this->cacheKey($userId));
    }

    private function cacheKey(string $userId): string
    {
        return sprintf(self::CACHE_KEY, $userId);
    }
}
