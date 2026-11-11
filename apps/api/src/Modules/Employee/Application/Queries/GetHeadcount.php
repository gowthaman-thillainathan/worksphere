<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Application\Queries;

use Meridian\Modules\Employee\Application\Data\Headcount;
use Meridian\Modules\Employee\Domain\Repositories\EmployeeRepository;

final readonly class GetHeadcount
{
    public function __construct(private EmployeeRepository $employees) {}

    public function handle(): Headcount
    {
        return new Headcount($this->employees->countByStatus());
    }
}
