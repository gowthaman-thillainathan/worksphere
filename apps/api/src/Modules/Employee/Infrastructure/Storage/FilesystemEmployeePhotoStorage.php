<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Infrastructure\Storage;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Meridian\Modules\Employee\Domain\Services\EmployeePhotoStorage;
use RuntimeException;

/**
 * Stores photos under {directory}/{employee id}/{random}.{ext} on a private
 * disk. Random names mean a replaced photo's URL can never be guessed.
 */
final readonly class FilesystemEmployeePhotoStorage implements EmployeePhotoStorage
{
    public function __construct(
        private Filesystem $disk,
        private string $directory,
    ) {}

    public function store(string $employeeId, $contents, string $extension): string
    {
        $path = sprintf('%s/%s/%s.%s', $this->directory, $employeeId, Str::uuid7(), $extension);

        if (! $this->disk->writeStream($path, $contents)) {
            throw new RuntimeException(sprintf('Could not store employee photo at %s.', $path));
        }

        return $path;
    }

    public function read(string $path)
    {
        return $this->disk->readStream($path);
    }

    public function delete(string $path): void
    {
        $this->disk->delete($path);
    }
}
