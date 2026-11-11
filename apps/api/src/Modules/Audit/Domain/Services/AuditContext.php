<?php

declare(strict_types=1);

namespace Meridian\Modules\Audit\Domain\Services;

/**
 * Port: who is acting and from where. Resolved from the HTTP request, or
 * empty (system actor) in console commands and queued jobs.
 */
interface AuditContext
{
    public function actorId(): ?string;

    public function ipAddress(): ?string;

    public function userAgent(): ?string;

    public function requestId(): ?string;
}
