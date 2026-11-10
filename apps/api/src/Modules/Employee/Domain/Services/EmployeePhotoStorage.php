<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Domain\Services;

/**
 * Port for storing profile photos outside the database (local disk in
 * development, private S3 bucket in production).
 */
interface EmployeePhotoStorage
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

    public function delete(string $path): void;
}
