<?php

declare(strict_types=1);

namespace Meridian\Modules\Documents\Domain\Repositories;

use Meridian\Modules\Documents\Domain\ValueObjects\DocumentCategory;
use Meridian\Modules\Documents\Infrastructure\Persistence\Models\Document;

interface DocumentRepository
{
    /**
     * Newest first.
     *
     * @param  list<DocumentCategory>  $categories
     * @return list<Document>
     */
    public function forEmployee(string $employeeId, array $categories): array;

    public function find(string $id): ?Document;

    public function save(Document $document): void;

    public function softDelete(Document $document): void;
}
