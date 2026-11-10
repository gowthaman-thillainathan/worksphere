<?php

declare(strict_types=1);

namespace Meridian\Modules\Audit\Domain\ValueObjects;

/**
 * Filters for browsing the audit trail. Results are always newest first.
 */
final readonly class AuditEventCriteria
{
    public function __construct(
        public ?string $subjectType = null,
        public ?string $subjectId = null,
        public ?string $actorId = null,
        public ?string $action = null,
        public int $perPage = 25,
        public ?string $cursor = null,
    ) {}
}
