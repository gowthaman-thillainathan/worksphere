<?php

declare(strict_types=1);

namespace Meridian\Modules\Documents\Application\Queries;

use Meridian\Modules\Documents\Domain\Exceptions\DocumentEmployeeNotFound;
use Meridian\Modules\Documents\Domain\Repositories\DocumentRepository;
use Meridian\Modules\Documents\Domain\Services\EmployeeLookup;
use Meridian\Modules\Documents\Domain\ValueObjects\DocumentCategory;
use Meridian\Modules\Documents\Infrastructure\Persistence\Models\Document;

final readonly class ListEmployeeDocuments
{
    public function __construct(
        private DocumentRepository $documents,
        private EmployeeLookup $employees,
    ) {}

    /**
     * Confidential documents are left out unless the user may see them.
     *
     * @return list<Document>
     *
     * @throws DocumentEmployeeNotFound
     */
    public function handle(string $employeeId, bool $canSeeConfidential): array
    {
        if (! $this->employees->isActive($employeeId)) {
            throw DocumentEmployeeNotFound::withId($employeeId);
        }

        return $this->documents->forEmployee($employeeId, DocumentCategory::visible($canSeeConfidential));
    }
}
