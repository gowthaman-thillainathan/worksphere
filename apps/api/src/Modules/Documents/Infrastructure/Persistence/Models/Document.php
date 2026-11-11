<?php

declare(strict_types=1);

namespace Meridian\Modules\Documents\Infrastructure\Persistence\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Meridian\Modules\Documents\Database\Factories\DocumentFactory;
use Meridian\Modules\Documents\Domain\ValueObjects\DocumentCategory;

/**
 * A file attached to an employee (docs/03 §7).
 *
 * @property string $id
 * @property string $employee_id
 * @property DocumentCategory $category
 * @property string $title
 * @property string $file_name Name of the uploaded file
 * @property string $path Storage path, never exposed
 * @property string $mime_type
 * @property int $size_bytes
 * @property string $checksum_sha256
 * @property CarbonImmutable|null $expires_on
 * @property string|null $uploaded_by
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
final class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    protected $table = 'documents';

    /**
     * @var list<string>
     */
    protected $fillable = ['employee_id', 'category', 'title', 'file_name', 'mime_type', 'size_bytes', 'expires_on'];

    /**
     * @var list<string>
     */
    protected $hidden = ['path'];

    protected static function newFactory(): DocumentFactory
    {
        return DocumentFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => DocumentCategory::class,
            'size_bytes' => 'integer',
            'expires_on' => 'immutable_date',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'deleted_at' => 'immutable_datetime',
        ];
    }
}
