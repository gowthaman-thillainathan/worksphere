<?php

declare(strict_types=1);

namespace Meridian\Modules\Audit\Application\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Database\ConnectionInterface;
use Meridian\Modules\Audit\Contracts\AuditRecord;
use Meridian\Modules\Audit\Contracts\AuditRecorder;
use Meridian\Modules\Audit\Domain\Repositories\AuditEventRepository;
use Meridian\Modules\Audit\Domain\Services\AuditChainHasher;
use Meridian\Modules\Audit\Domain\Services\AuditContext;
use Meridian\Modules\Audit\Domain\ValueObjects\ActorType;
use Meridian\Modules\Audit\Domain\ValueObjects\AuditEventData;

/**
 * Appends an event to the hash chain. Joins the caller's transaction (or
 * opens one), so the audit record commits atomically with the change.
 */
final readonly class RecordAuditEvent implements AuditRecorder
{
    public function __construct(
        private AuditEventRepository $events,
        private AuditContext $context,
        private ConnectionInterface $database,
    ) {}

    public function record(AuditRecord $record): void
    {
        $actorId = $this->context->actorId();

        $event = new AuditEventData(
            occurredAt: CarbonImmutable::now()->startOfSecond(),
            actorType: $actorId === null ? ActorType::System : ActorType::User,
            actorId: $actorId,
            action: $record->action,
            subjectType: $record->subjectType,
            subjectId: $record->subjectId,
            changes: $record->changes,
            ipAddress: $this->context->ipAddress(),
            userAgent: $this->context->userAgent(),
            requestId: $this->context->requestId(),
        );

        $this->database->transaction(function () use ($event): void {
            $previousHash = $this->events->lockChainHead();
            $this->events->append($event, $previousHash, AuditChainHasher::hash($previousHash, $event));
        });
    }
}
