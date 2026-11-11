<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Application\Data;

final readonly class UploadedPhoto
{
    public function __construct(
        public string $temporaryPath,
        public string $extension,
        public string $mimeType,
    ) {}
}
