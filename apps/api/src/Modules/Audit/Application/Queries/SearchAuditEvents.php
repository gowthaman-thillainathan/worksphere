<?php

declare(strict_types=1);

namespace Meridian\Modules\Audit\Application\Queries;

use Meridian\Modules\Audit\Domain\Repositories\AuditEventRepository;
use Meridian\Modules\Audit\Domain\ValueObjects\AuditEventCriteria;
use Meridian\Modules\Audit\Infrastructure\Persistence\Models\AuditEvent;
use Meridian\Shared\Domain\ValueObjects\CursorPage;

final readonly class SearchAuditEvents
{
    public function __construct(private AuditEventRepository $events) {}

    /**
     * @return CursorPage<AuditEvent>
     */
    public function handle(AuditEventCriteria $criteria): CursorPage
    {
        return $this->events->search($criteria);
    }
}
