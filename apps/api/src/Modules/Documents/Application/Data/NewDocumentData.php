<?php

declare(strict_types=1);

namespace Meridian\Modules\Documents\Application\Data;

use DateTimeImmutable;
use Meridian\Modules\Documents\Domain\ValueObjects\DocumentCategory;

final readonly class NewDocumentData
{
    public function __construct(
        public string $employeeId,
        public string $title,
        public DocumentCategory $category,
        public UploadedDocument $file,
        public ?DateTimeImmutable $expiresOn = null,
        public ?string $uploadedBy = null,
    ) {}
}
