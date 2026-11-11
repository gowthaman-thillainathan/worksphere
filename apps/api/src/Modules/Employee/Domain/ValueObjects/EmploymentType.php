<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Domain\ValueObjects;

enum EmploymentType: string
{
    case FullTime = 'full_time';
    case PartTime = 'part_time';
    case Contract = 'contract';
    case Intern = 'intern';
}
