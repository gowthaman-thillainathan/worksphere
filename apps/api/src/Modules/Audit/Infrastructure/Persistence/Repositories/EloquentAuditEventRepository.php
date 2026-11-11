<?php

declare(strict_types=1);

namespace Meridian\Modules\Audit\Infrastructure\Persistence\Repositories;

use Meridian\Modules\Audit\Domain\Repositories\AuditEventRepository;
use Meridian\Modules\Audit\Domain\ValueObjects\AuditEventCriteria;
use Meridian\Modules\Audit\Domain\ValueObjects\AuditEventData;
use Meridian\Modules\Audit\Infrastructure\Persistence\Models\AuditChainHead;
use Meridian\Modules\Audit\Infrastructure\Persistence\Models\AuditEvent;
use Meridian\Shared\Domain\ValueObjects\CursorPage;

final class EloquentAuditEventRepository implements AuditEventRepository
{
    private const CHUNK_SIZE = 500;

    public function lockChainHead(): ?string
    {
        $head = AuditChainHead::query()->lockForUpdate()->findOrFail(AuditChainHead::DEFAULT_CHAIN);

        return $head->last_hash;
    }

    public function append(AuditEventData $event, ?string $previousHash, string $hash): void
    {
        AuditEvent::query()->create([
            'occurred_at' => $event->occurredAt,
            'actor_type' => $event->actorType,
            'actor_id' => $event->actorId,
            'action' => $event->action,
            'subject_type' => $event->subjectType,
            'subject_id' => $event->subjectId,
            'changes' => $event->changes,
            'ip_address' => $event->ipAddress,
            'user_agent' => $event->userAgent,
            'request_id' => $event->requestId,
            'prev_hash' => $previousHash,
            'hash' => $hash,
        ]);

        AuditChainHead::query()->whereKey(AuditChainHead::DEFAULT_CHAIN)->update(['last_hash' => $hash]);
    }

    public function search(AuditEventCriteria $criteria): CursorPage
    {
        $paginator = AuditEvent::query()
            ->when($criteria->subjectType, static fn ($query, string $value) => $query->where('subject_type', $value))
            ->when($criteria->subjectId, static fn ($query, string $value) => $query->where('subject_id', $value))
            ->when($criteria->actorId, static fn ($query, string $value) => $query->where('actor_id', $value))
            ->when($criteria->action, static fn ($query, string $value) => $query->where('action', $value))
            ->orderByDesc('id')
            ->cursorPaginate(perPage: $criteria->perPage, cursor: $criteria->cursor);

        /** @var list<AuditEvent> $items */
        $items = $paginator->items();

        return new CursorPage(
            items: $items,
            perPage: $paginator->perPage(),
            nextCursor: $paginator->nextCursor()?->encode(),
            previousCursor: $paginator->previousCursor()?->encode(),
        );
    }

    public function inChainOrder(): iterable
    {
        return AuditEvent::query()->orderBy('id')->lazyById(self::CHUNK_SIZE);
    }
}
