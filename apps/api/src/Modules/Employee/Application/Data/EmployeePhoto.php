<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Application\Data;

final readonly class EmployeePhoto
{
    /**
     * @param  resource  $stream
     */
    public function __construct(
        public mixed $stream,
        public string $mimeType,
    ) {}
}
