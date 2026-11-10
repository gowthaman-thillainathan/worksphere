<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Gap-tolerant counter for employee numbers. The row is locked while a
 * number is reserved, so concurrent hires never get the same number.
 *
 * @property string $name
 * @property int $last_value
 */
final class EmployeeNumberSequence extends Model
{
    public const EMPLOYEES = 'employees';

    public $timestamps = false;

    public $incrementing = false;

    protected $table = 'employee_number_sequences';

    protected $primaryKey = 'name';

    protected $keyType = 'string';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['last_value' => 'integer'];
    }
}
