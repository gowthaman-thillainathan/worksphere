<?php

declare(strict_types=1);

namespace Meridian\Modules\Documents\Application\Data;

final readonly class UploadedDocument
{
    public function __construct(
        public string $temporaryPath,
        public string $originalName,
        public string $extension,
        public string $mimeType,
        public int $sizeBytes,
    ) {}
}
