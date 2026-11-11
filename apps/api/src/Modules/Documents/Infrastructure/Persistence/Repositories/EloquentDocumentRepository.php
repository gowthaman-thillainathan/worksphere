<?php

declare(strict_types=1);

namespace Meridian\Modules\Documents\Infrastructure\Persistence\Repositories;

use Meridian\Modules\Documents\Domain\Repositories\DocumentRepository;
use Meridian\Modules\Documents\Domain\ValueObjects\DocumentCategory;
use Meridian\Modules\Documents\Infrastructure\Persistence\Models\Document;

final class EloquentDocumentRepository implements DocumentRepository
{
    public function forEmployee(string $employeeId, array $categories): array
    {
        return Document::query()
            ->where('employee_id', $employeeId)
            ->whereIn('category', array_map(static fn (DocumentCategory $category): string => $category->value, $categories))
            ->latest()
            ->orderBy('id')
            ->get()
            ->values()
            ->all();
    }

    public function find(string $id): ?Document
    {
        return Document::query()->find($id);
    }

    public function save(Document $document): void
    {
        $document->save();
    }

    public function softDelete(Document $document): void
    {
        $document->delete();
    }
}
