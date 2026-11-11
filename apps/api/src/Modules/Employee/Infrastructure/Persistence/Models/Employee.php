<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Infrastructure\Persistence\Models;

use BackedEnum;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Meridian\Modules\Employee\Database\Factories\EmployeeFactory;
use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeStatus;
use Meridian\Modules\Employee\Domain\ValueObjects\EmploymentType;

/**
 * Employee profile. Personal data is encrypted at rest with the application
 * key (docs/03 §5); a database dump alone does not reveal it.
 *
 * Location and position links arrive with later Organisation work.
 *
 * @property string $id
 * @property string $employee_number
 * @property string $first_name
 * @property string $last_name
 * @property string|null $preferred_name
 * @property string $work_email
 * @property string|null $personal_email
 * @property string|null $phone
 * @property string|null $date_of_birth
 * @property string $job_title
 * @property EmploymentType $employment_type
 * @property EmployeeStatus $status
 * @property CarbonImmutable $hire_date
 * @property CarbonImmutable|null $termination_date
 * @property string|null $manager_id
 * @property string|null $department_id
 * @property string|null $photo_path
 * @property string|null $photo_mime_type
 * @property int $version
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Employee|null $manager
 */
final class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    /**
     * Personal fields: encrypted, only visible with employee.view.sensitive,
     * only writable with employee.update.sensitive, masked in the audit log.
     */
    public const SENSITIVE_FIELDS = ['personal_email', 'phone', 'date_of_birth'];

    /**
     * Fields clients may write through the API.
     */
    public const EDITABLE_FIELDS = [
        'first_name', 'last_name', 'preferred_name', 'work_email', 'job_title',
        'employment_type', 'status', 'hire_date', 'termination_date', 'manager_id', 'department_id',
        ...self::SENSITIVE_FIELDS,
    ];

    private const DATE_FORMAT = 'Y-m-d';

    protected $table = 'employees';

    /**
     * @var list<string>
     */
    protected $fillable = self::EDITABLE_FIELDS;

    /**
     * @var list<string>
     */
    protected $hidden = ['photo_path', ...self::SENSITIVE_FIELDS];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'version' => 1,
    ];

    public function displayName(): string
    {
        return ($this->preferred_name ?? $this->first_name).' '.$this->last_name;
    }

    public function hasPhoto(): bool
    {
        return $this->photo_path !== null;
    }

    /**
     * Plain values of the editable fields, used for audit diffs.
     *
     * @return array<string, string|null>
     */
    public function auditSnapshot(): array
    {
        $snapshot = [];

        foreach (self::EDITABLE_FIELDS as $field) {
            $value = $this->getAttribute($field);
            $snapshot[$field] = match (true) {
                $value instanceof CarbonImmutable => $value->format(self::DATE_FORMAT),
                $value instanceof BackedEnum => (string) $value->value,
                $value === null => null,
                default => (string) $value,
            };
        }

        return $snapshot;
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    /**
     * Work e-mail addresses are compared case-insensitively.
     *
     * @return Attribute<string, string>
     */
    protected function workEmail(): Attribute
    {
        return Attribute::make(set: static fn (string $value): string => mb_strtolower(trim($value)));
    }

    protected static function newFactory(): EmployeeFactory
    {
        return EmployeeFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'employment_type' => EmploymentType::class,
            'status' => EmployeeStatus::class,
            'hire_date' => 'immutable_date',
            'termination_date' => 'immutable_date',
            'personal_email' => 'encrypted',
            'phone' => 'encrypted',
            'date_of_birth' => 'encrypted',
            'version' => 'integer',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'deleted_at' => 'immutable_datetime',
        ];
    }
}
