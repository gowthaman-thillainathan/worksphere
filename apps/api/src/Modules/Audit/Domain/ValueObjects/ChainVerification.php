<?php

declare(strict_types=1);

namespace Meridian\Modules\Audit\Domain\ValueObjects;

final readonly class ChainVerification
{
    public function __construct(
        public int $eventsChecked,
        public ?int $firstBrokenEventId,
    ) {}

    public function isIntact(): bool
    {
        return $this->firstBrokenEventId === null;
    }
}
