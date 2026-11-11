<?php

declare(strict_types=1);

namespace Meridian\Modules\Audit\Contracts;

/**
 * What happened to which subject. The actor, time, IP and request id are
 * captured by the recorder.
 */
final readonly class AuditRecord
{
    /**
     * @param  string  $action  Past-tense event name, e.g. "employee.updated"
     * @param  array<string, array{0: mixed, 1: mixed}>  $changes  {field: [old, new]}
     */
    public function __construct(
        public string $action,
        public string $subjectType,
        public string $subjectId,
        public array $changes = [],
    ) {}
}
