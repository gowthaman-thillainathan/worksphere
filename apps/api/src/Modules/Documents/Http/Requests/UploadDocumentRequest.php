<?php

declare(strict_types=1);

namespace Meridian\Modules\Documents\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Meridian\Modules\Documents\Application\Data\NewDocumentData;
use Meridian\Modules\Documents\Application\Data\UploadedDocument;
use Meridian\Modules\Documents\Domain\ValueObjects\DocumentCategory;
use Meridian\Shared\Http\CurrentActor;

/**
 * multipart/form-data: file, title, category, optional expires_on.
 * The file type is detected from its contents.
 */
final class UploadDocumentRequest extends FormRequest
{
    private const DATE_FORMAT = 'date_format:Y-m-d';

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', File::types(config('documents.mimes'))->max((int) config('documents.max_kilobytes'))],
            'title' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::enum(DocumentCategory::class)],
            'expires_on' => ['sometimes', 'nullable', self::DATE_FORMAT, 'after:today'],
        ];
    }

    public function toData(string $employeeId): NewDocumentData
    {
        /** @var UploadedFile $file */
        $file = $this->file('file');

        return new NewDocumentData(
            employeeId: $employeeId,
            title: $this->string('title')->trim()->toString(),
            category: $this->enum('category', DocumentCategory::class) ?? DocumentCategory::Other,
            file: new UploadedDocument(
                temporaryPath: (string) $file->getRealPath(),
                originalName: $file->getClientOriginalName(),
                extension: (string) $file->extension(),
                mimeType: (string) $file->getMimeType(),
                sizeBytes: (int) $file->getSize(),
            ),
            expiresOn: $this->filled('expires_on') ? CarbonImmutable::parse($this->string('expires_on')->toString()) : null,
            uploadedBy: CurrentActor::id($this),
        );
    }
}
