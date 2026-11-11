<?php

declare(strict_types=1);

namespace Meridian\Modules\Documents\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Meridian\Modules\Documents\Infrastructure\Persistence\Models\Document;

/**
 * @mixin Document
 */
final class DocumentResource extends JsonResource
{
    private const DATE_FORMAT = 'Y-m-d';

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'document',
            'employee_id' => $this->employee_id,
            'title' => $this->title,
            'category' => $this->category->value,
            'confidential' => $this->category->isConfidential(),
            'file_name' => $this->file_name,
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
            'expires_on' => $this->expires_on?->format(self::DATE_FORMAT),
            'download_url' => route('documents.download', $this->id),
            'uploaded_by' => $this->uploaded_by,
            'created_at' => $this->created_at->toIso8601ZuluString(),
        ];
    }
}
