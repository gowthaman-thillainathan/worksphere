<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rules\Dimensions;
use Illuminate\Validation\Rules\File;
use Meridian\Modules\Employee\Application\Data\UploadedPhoto;

/**
 * multipart/form-data with a single "photo" image. The type is detected from
 * the file contents, not the client-supplied name or MIME type.
 */
final class UploadEmployeePhotoRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $min = (int) config('employee.photo.min_dimension');
        $max = (int) config('employee.photo.max_dimension');

        return [
            'photo' => [
                'required',
                File::image()
                    ->types(config('employee.photo.mimes'))
                    ->max((int) config('employee.photo.max_kilobytes'))
                    ->dimensions((new Dimensions)->minWidth($min)->minHeight($min)->maxWidth($max)->maxHeight($max)),
            ],
        ];
    }

    public function toPhoto(): UploadedPhoto
    {
        /** @var UploadedFile $file */
        $file = $this->file('photo');

        return new UploadedPhoto(
            temporaryPath: (string) $file->getRealPath(),
            extension: (string) $file->extension(),
            mimeType: (string) $file->getMimeType(),
        );
    }
}
