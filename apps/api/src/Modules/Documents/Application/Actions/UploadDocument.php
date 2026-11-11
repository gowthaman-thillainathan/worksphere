<?php

declare(strict_types=1);

namespace Meridian\Modules\Documents\Application\Actions;

use Illuminate\Database\ConnectionInterface;
use Meridian\Modules\Audit\Contracts\AuditRecord;
use Meridian\Modules\Audit\Contracts\AuditRecorder;
use Meridian\Modules\Documents\Application\Data\NewDocumentData;
use Meridian\Modules\Documents\Domain\Exceptions\DocumentEmployeeNotFound;
use Meridian\Modules\Documents\Domain\Repositories\DocumentRepository;
use Meridian\Modules\Documents\Domain\Services\DocumentStorage;
use Meridian\Modules\Documents\Domain\Services\EmployeeLookup;
use Meridian\Modules\Documents\Domain\ValueObjects\DocumentAuditAction;
use Meridian\Modules\Documents\Infrastructure\Persistence\Models\Document;
use RuntimeException;

/**
 * Stores the file, then the record with its SHA-256 checksum, and records
 * document.uploaded on the employee's history.
 */
final readonly class UploadDocument
{
    private const CHECKSUM_ALGORITHM = 'sha256';

    public function __construct(
        private DocumentRepository $documents,
        private DocumentStorage $storage,
        private EmployeeLookup $employees,
        private AuditRecorder $audit,
        private ConnectionInterface $database,
    ) {}

    /**
     * @throws DocumentEmployeeNotFound
     */
    public function handle(NewDocumentData $data): Document
    {
        if (! $this->employees->isActive($data->employeeId)) {
            throw DocumentEmployeeNotFound::withId($data->employeeId);
        }

        $stream = fopen($data->file->temporaryPath, 'rb') ?: throw new RuntimeException('Could not read the uploaded document.');

        try {
            $path = $this->storage->store($data->employeeId, $stream, $data->file->extension);
        } finally {
            fclose($stream);
        }

        return $this->database->transaction(function () use ($data, $path): Document {
            $document = new Document([
                'employee_id' => $data->employeeId,
                'category' => $data->category,
                'title' => $data->title,
                'file_name' => $data->file->originalName,
                'mime_type' => $data->file->mimeType,
                'size_bytes' => $data->file->sizeBytes,
                'expires_on' => $data->expiresOn,
            ]);
            $document->path = $path;
            $document->checksum_sha256 = (string) hash_file(self::CHECKSUM_ALGORITHM, $data->file->temporaryPath);
            $document->uploaded_by = $data->uploadedBy;
            $this->documents->save($document);

            $this->audit->record(new AuditRecord(
                action: DocumentAuditAction::Uploaded->value,
                subjectType: DocumentAuditAction::SUBJECT_TYPE,
                subjectId: $data->employeeId,
                changes: [DocumentAuditAction::DOCUMENT_FIELD => [null, $document->title]],
            ));

            return $document;
        });
    }
}
