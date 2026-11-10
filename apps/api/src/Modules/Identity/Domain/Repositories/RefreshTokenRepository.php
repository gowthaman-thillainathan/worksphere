<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Domain\Repositories;

use DateTimeImmutable;
use Meridian\Modules\Identity\Infrastructure\Persistence\Models\RefreshToken;

interface RefreshTokenRepository
{
    public function create(
        string $userId,
        string $familyId,
        string $tokenHash,
        DateTimeImmutable $expiresAt,
        ?string $ipAddress,
        ?string $userAgent,
    ): RefreshToken;

    public function findByHash(string $tokenHash): ?RefreshToken;

    /**
     * Loads the token and locks its row until the surrounding transaction ends,
     * so two concurrent refreshes cannot both rotate the same token.
     */
    public function findByHashForUpdate(string $tokenHash): ?RefreshToken;

    public function markReplaced(RefreshToken $token, string $replacementId, DateTimeImmutable $at): void;

    /**
     * @return int Number of tokens revoked.
     */
    public function revokeFamily(string $familyId, DateTimeImmutable $at): int;

    /**
     * @return int Number of tokens revoked.
     */
    public function revokeAllForUser(string $userId, DateTimeImmutable $at): int;
}
