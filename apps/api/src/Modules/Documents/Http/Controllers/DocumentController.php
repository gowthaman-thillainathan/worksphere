<?php

declare(strict_types=1);

namespace Meridian\Modules\Documents\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Meridian\Modules\Access\Contracts\Permission;
use Meridian\Modules\Documents\Application\Actions\DeleteDocument;
use Meridian\Modules\Documents\Application\Actions\UploadDocument;
use Meridian\Modules\Documents\Application\Queries\GetDocumentFile;
use Meridian\Modules\Documents\Application\Queries\ListEmployeeDocuments;
use Meridian\Modules\Documents\Http\Requests\UploadDocumentRequest;
use Meridian\Modules\Documents\Http\Resources\DocumentResource;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response as HttpStatus;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class DocumentController
{
    /**
     * GET /employees/{employee}/documents
     */
    public function index(Request $request, string $employee, ListEmployeeDocuments $listDocuments): AnonymousResourceCollection
    {
        return DocumentResource::collection($listDocuments->handle($employee, self::canSeeConfidential($request)));
    }

    /**
     * POST /employees/{employee}/documents (multipart/form-data)
     */
    public function store(UploadDocumentRequest $request, string $employee, UploadDocument $uploadDocument): JsonResponse
    {
        return (new DocumentResource($uploadDocument->handle($request->toData($employee))))
            ->response()
            ->setStatusCode(HttpStatus::HTTP_CREATED);
    }

    /**
     * GET /documents/{document}/download — streamed as an attachment.
     */
    public function download(Request $request, string $document, GetDocumentFile $getFile): StreamedResponse
    {
        $file = $getFile->handle($document, self::canSeeConfidential($request));

        return response()->stream(
            static function () use ($file): void {
                fpassthru($file->stream);
                fclose($file->stream);
            },
            headers: [
                'Content-Type' => $file->mimeType,
                'Content-Length' => (string) $file->sizeBytes,
                'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $file->fileName, self::asciiFallback($file->fileName)),
            ],
        );
    }

    /**
     * DELETE /documents/{document}
     */
    public function destroy(Request $request, string $document, DeleteDocument $deleteDocument): Response
    {
        $deleteDocument->handle($document, self::canSeeConfidential($request));

        return response()->noContent();
    }

    private static function canSeeConfidential(Request $request): bool
    {
        return (bool) $request->user()?->can(Permission::DocumentViewConfidential->value);
    }

    private static function asciiFallback(string $fileName): string
    {
        return (string) preg_replace('/[^\x20-\x7E]|[%\/\\\\]/', '_', $fileName);
    }
}
