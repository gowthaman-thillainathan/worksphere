<?php

declare(strict_types=1);

namespace Meridian\Modules\Audit\Domain\ValueObjects;

use DateTimeImmutable;
use DateTimeZone;

/**
 * The immutable content of an audit event — exactly the fields covered by
 * its hash.
 */
final readonly class AuditEventData
{
    private const TIMESTAMP_FORMAT = 'Y-m-d\TH:i:s\Z';

    /**
     * @param  array<string, mixed>  $changes
     */
    public function __construct(
        public DateTimeImmutable $occurredAt,
        public ActorType $actorType,
        public ?string $actorId,
        public string $action,
        public string $subjectType,
        public string $subjectId,
        public array $changes,
        public ?string $ipAddress,
        public ?string $userAgent,
        public ?string $requestId,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toHashPayload(): array
    {
        return [
            'occurred_at' => $this->occurredAt->setTimezone(new DateTimeZone('UTC'))->format(self::TIMESTAMP_FORMAT),
            'actor_type' => $this->actorType->value,
            'actor_id' => $this->actorId,
            'action' => $this->action,
            'subject_type' => $this->subjectType,
            'subject_id' => $this->subjectId,
            'changes' => $this->changes,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'request_id' => $this->requestId,
        ];
    }
}
