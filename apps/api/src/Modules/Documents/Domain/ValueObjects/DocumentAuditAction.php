<?php

declare(strict_types=1);

namespace Meridian\Modules\Documents\Domain\ValueObjects;

/**
 * Document events are recorded on the employee they belong to, so they
 * appear in the employee's history.
 */
enum DocumentAuditAction: string
{
    public const SUBJECT_TYPE = 'employee';

    public const DOCUMENT_FIELD = 'document';

    case Uploaded = 'document.uploaded';
    case Deleted = 'document.deleted';
}
