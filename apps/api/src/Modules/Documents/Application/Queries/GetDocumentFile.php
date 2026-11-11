<?php

declare(strict_types=1);

namespace Meridian\Modules\Documents\Application\Queries;

use Meridian\Modules\Documents\Application\Data\DocumentFile;
use Meridian\Modules\Documents\Domain\Exceptions\DocumentNotFound;
use Meridian\Modules\Documents\Domain\Repositories\DocumentRepository;
use Meridian\Modules\Documents\Domain\Services\DocumentStorage;

final readonly class GetDocumentFile
{
    public function __construct(
        private DocumentRepository $documents,
        private DocumentStorage $storage,
    ) {}

    /**
     * @throws DocumentNotFound
     */
    public function handle(string $documentId, bool $canSeeConfidential): DocumentFile
    {
        $document = $this->documents->find($documentId);

        if ($document === null || ($document->category->isConfidential() && ! $canSeeConfidential)) {
            throw DocumentNotFound::withId($documentId);
        }

        $stream = $this->storage->read($document->path) ?? throw DocumentNotFound::withId($documentId);

        return new DocumentFile($stream, $document->file_name, $document->mime_type, $document->size_bytes);
    }
}
