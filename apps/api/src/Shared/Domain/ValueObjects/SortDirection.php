<?php

declare(strict_types=1);

namespace Meridian\Shared\Domain\ValueObjects;

enum SortDirection: string
{
    case Ascending = 'asc';
    case Descending = 'desc';
}
