<?php

declare(strict_types=1);

namespace Meridian\Modules\Documents\Domain\Services;

/**
 * Port for document files (private local disk in development, private S3
 * bucket in production). Files of soft-deleted documents are kept, so a
 * deletion can be undone by an administrator.
 */
interface DocumentStorage
{
    /**
     * @param  resource  $contents
     * @return string The stored file's path.
     */
    public function store(string $employeeId, $contents, string $extension): string;

    /**
     * @return resource|null
     */
    public function read(string $path);
}
