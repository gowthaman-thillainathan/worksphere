<?php

declare(strict_types=1);

namespace Meridian\Shared\Http\Query;

use Meridian\Shared\Domain\ValueObjects\SortDirection;

final readonly class SortInstruction
{
    public function __construct(
        public string $field,
        public SortDirection $direction,
    ) {}
}
