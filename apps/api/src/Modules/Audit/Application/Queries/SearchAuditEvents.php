<?php

declare(strict_types=1);

namespace Meridian\Modules\Audit\Application\Queries;

use Meridian\Modules\Audit\Contracts\ActorDirectory;
use Meridian\Modules\Audit\Domain\Repositories\AuditEventRepository;
use Meridian\Modules\Audit\Domain\ValueObjects\AuditEventCriteria;
use Meridian\Modules\Audit\Infrastructure\Persistence\Models\AuditEvent;
use Meridian\Shared\Domain\ValueObjects\CursorPage;

final readonly class SearchAuditEvents
{
    public function __construct(
        private AuditEventRepository $events,
        private ActorDirectory $actors,
    ) {}

    /**
     * One page of events with actor names resolved in a single lookup.
     *
     * @return CursorPage<AuditEvent>
     */
    public function handle(AuditEventCriteria $criteria): CursorPage
    {
        $page = $this->events->search($criteria);

        $actorIds = array_values(array_unique(array_filter(array_map(
            static fn (AuditEvent $event): ?string => $event->actor_id,
            $page->items,
        ))));

        $names = $actorIds === [] ? [] : $this->actors->namesOf($actorIds);

        foreach ($page->items as $event) {
            $event->actorName = $event->actor_id === null ? null : ($names[$event->actor_id] ?? null);
        }

        return $page;
    }
}
