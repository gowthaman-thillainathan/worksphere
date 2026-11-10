<?php

declare(strict_types=1);

namespace Meridian\Modules\Audit\Domain\Repositories;

use Meridian\Modules\Audit\Domain\ValueObjects\AuditEventCriteria;
use Meridian\Modules\Audit\Domain\ValueObjects\AuditEventData;
use Meridian\Modules\Audit\Infrastructure\Persistence\Models\AuditEvent;
use Meridian\Shared\Domain\ValueObjects\CursorPage;

interface AuditEventRepository
{
    /**
     * Locks the chain head until the surrounding transaction ends, so events
     * are appended strictly one after another.
     *
     * @return string|null Hash of the latest event, null for an empty chain.
     */
    public function lockChainHead(): ?string;

    public function append(AuditEventData $event, ?string $previousHash, string $hash): void;

    /**
     * @return CursorPage<AuditEvent>
     */
    public function search(AuditEventCriteria $criteria): CursorPage;

    /**
     * Streams every event in chain order.
     *
     * @return iterable<AuditEvent>
     */
    public function inChainOrder(): iterable;
}
