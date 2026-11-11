<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Domain\ValueObjects;

use DateTimeImmutable;
use Meridian\Shared\Domain\ValueObjects\SortDirection;

/**
 * Everything a client can ask of the employee directory: free-text search,
 * filters (values within one filter are OR-ed, filters are AND-ed), sort
 * order and cursor pagination.
 */
final readonly class EmployeeSearchCriteria
{
    public const DEFAULT_PER_PAGE = 25;

    /** @var list<EmployeeSort> */
    public array $sort;

    /**
     * @param  list<EmployeeStatus>  $statuses
     * @param  list<EmploymentType>  $employmentTypes
     * @param  list<EmployeeSort>  $sort
     */
    public function __construct(
        public ?string $search = null,
        public array $statuses = [],
        public array $employmentTypes = [],
        public ?string $managerId = null,
        public ?string $departmentId = null,
        public ?DateTimeImmutable $hiredFrom = null,
        public ?DateTimeImmutable $hiredTo = null,
        public TrashedFilter $trashed = TrashedFilter::Without,
        array $sort = [],
        public int $perPage = self::DEFAULT_PER_PAGE,
        public ?string $cursor = null,
    ) {
        $this->sort = $sort !== [] ? $sort : [
            new EmployeeSort(EmployeeSortField::LastName, SortDirection::Ascending),
            new EmployeeSort(EmployeeSortField::FirstName, SortDirection::Ascending),
        ];
    }
}
