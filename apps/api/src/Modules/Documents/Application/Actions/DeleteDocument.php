<?php

declare(strict_types=1);

namespace Meridian\Modules\Documents\Application\Actions;

use Illuminate\Database\ConnectionInterface;
use Meridian\Modules\Audit\Contracts\AuditRecord;
use Meridian\Modules\Audit\Contracts\AuditRecorder;
use Meridian\Modules\Documents\Domain\Exceptions\DocumentNotFound;
use Meridian\Modules\Documents\Domain\Repositories\DocumentRepository;
use Meridian\Modules\Documents\Domain\ValueObjects\DocumentAuditAction;

/**
 * Soft delete: the file is kept for recovery and retention rules.
 */
final readonly class DeleteDocument
{
    public function __construct(
        private DocumentRepository $documents,
        private AuditRecorder $audit,
        private ConnectionInterface $database,
    ) {}

    /**
     * @throws DocumentNotFound
     */
    public function handle(string $documentId, bool $canSeeConfidential): void
    {
        $this->database->transaction(function () use ($documentId, $canSeeConfidential): void {
            $document = $this->documents->find($documentId);

            if ($document === null || ($document->category->isConfidential() && ! $canSeeConfidential)) {
                throw DocumentNotFound::withId($documentId);
            }

            $this->documents->softDelete($document);

            $this->audit->record(new AuditRecord(
                action: DocumentAuditAction::Deleted->value,
                subjectType: DocumentAuditAction::SUBJECT_TYPE,
                subjectId: $document->employee_id,
                changes: [DocumentAuditAction::DOCUMENT_FIELD => [$document->title, null]],
            ));
        });
    }
}
