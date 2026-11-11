<?php

declare(strict_types=1);

namespace Meridian\Modules\Documents\Infrastructure\Storage;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Meridian\Modules\Documents\Domain\Services\DocumentStorage;
use RuntimeException;

/**
 * Stores files under {directory}/{employee id}/{random}.{ext} on a private
 * disk. The original file name is kept in the database only.
 */
final readonly class FilesystemDocumentStorage implements DocumentStorage
{
    public function __construct(
        private Filesystem $disk,
        private string $directory,
    ) {}

    public function store(string $employeeId, $contents, string $extension): string
    {
        $path = sprintf('%s/%s/%s.%s', $this->directory, $employeeId, Str::uuid7(), $extension);

        if (! $this->disk->writeStream($path, $contents)) {
            throw new RuntimeException(sprintf('Could not store document at %s.', $path));
        }

        return $path;
    }

    public function read(string $path)
    {
        return $this->disk->readStream($path);
    }
}
