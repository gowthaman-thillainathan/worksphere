<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Meridian\Modules\Employee\Application\Actions\RemoveEmployeePhoto;
use Meridian\Modules\Employee\Application\Actions\UpdateEmployeePhoto;
use Meridian\Modules\Employee\Application\Queries\GetEmployeePhoto;
use Meridian\Modules\Employee\Http\Requests\UploadEmployeePhotoRequest;
use Meridian\Modules\Employee\Http\Resources\EmployeeResource;
use Meridian\Shared\Http\Concurrency\EntityTag;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class EmployeePhotoController
{
    /**
     * GET /employees/{employee}/photo — streamed through the API so the
     * photo is only ever served to authorised users.
     */
    public function show(string $employee, GetEmployeePhoto $getEmployeePhoto): StreamedResponse
    {
        $photo = $getEmployeePhoto->handle($employee);

        return response()->stream(
            static function () use ($photo): void {
                fpassthru($photo->stream);
                fclose($photo->stream);
            },
            headers: ['Content-Type' => $photo->mimeType, 'Content-Disposition' => 'inline'],
        );
    }

    /**
     * POST /employees/{employee}/photo (multipart/form-data, field "photo")
     */
    public function store(UploadEmployeePhotoRequest $request, string $employee, UpdateEmployeePhoto $updateEmployeePhoto): JsonResponse
    {
        $updated = $updateEmployeePhoto->handle($employee, $request->toPhoto());

        return (new EmployeeResource($updated))
            ->response()
            ->header(EntityTag::RESPONSE_HEADER, EntityTag::forVersion($updated->version));
    }

    /**
     * DELETE /employees/{employee}/photo
     */
    public function destroy(string $employee, RemoveEmployeePhoto $removeEmployeePhoto): Response
    {
        $removeEmployeePhoto->handle($employee);

        return response()->noContent();
    }
}
