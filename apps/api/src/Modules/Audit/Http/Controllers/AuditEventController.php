<?php

declare(strict_types=1);

namespace Meridian\Modules\Audit\Http\Controllers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Meridian\Modules\Audit\Application\Queries\SearchAuditEvents;
use Meridian\Modules\Audit\Http\Requests\ListAuditEventsRequest;
use Meridian\Modules\Audit\Http\Resources\AuditEventResource;
use Meridian\Shared\Http\Resources\CursorPageResponse;

final readonly class AuditEventController
{
    public function __construct(private SearchAuditEvents $searchAuditEvents) {}

    /**
     * GET /audit-events — newest first (requires audit.view).
     */
    public function index(ListAuditEventsRequest $request): AnonymousResourceCollection
    {
        return CursorPageResponse::make(
            $this->searchAuditEvents->handle($request->toCriteria()),
            AuditEventResource::class,
            $request,
        );
    }
}
