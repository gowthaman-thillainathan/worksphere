<?php

declare(strict_types=1);

namespace Meridian\Modules\Audit\Contracts;

/**
 * Records business-level audit events (docs/09 §6).
 *
 * Call it inside the same database transaction as the change being audited,
 * so the change and its audit record are committed or rolled back together.
 */
interface AuditRecorder
{
    public function record(AuditRecord $record): void;
}
