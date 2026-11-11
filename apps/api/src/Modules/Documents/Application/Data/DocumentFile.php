<?php

declare(strict_types=1);

namespace Meridian\Modules\Documents\Application\Data;

/**
 * A document's contents, ready to stream to the client.
 */
final readonly class DocumentFile
{
    /**
     * @param  resource  $stream
     */
    public function __construct(
        public mixed $stream,
        public string $fileName,
        public string $mimeType,
        public int $sizeBytes,
    ) {}
}
