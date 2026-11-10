<?php

declare(strict_types=1);

namespace Meridian\Modules\Audit\Domain\ValueObjects;

enum ActorType: string
{
    case User = 'user';
    case System = 'system';
}
