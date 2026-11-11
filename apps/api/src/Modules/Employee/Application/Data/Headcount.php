<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Application\Data;

final readonly class Headcount
{
    /**
     * @param  array<string, int>  $byStatus
     */
    public function __construct(public array $byStatus) {}

    public function total(): int
    {
        return array_sum($this->byStatus);
    }
}
