<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Domain\ValueObjects;

/**
 * Whether soft-deleted employees are part of a listing.
 */
enum TrashedFilter: string
{
    case Without = 'without';
    case With = 'with';
    case Only = 'only';
}
