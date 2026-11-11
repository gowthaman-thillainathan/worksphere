<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Meridian\Modules\Organisation\Contracts\DepartmentDirectory;

/**
 * - user_id: the employee's optional sign-in account (docs/03 §4, ADR-0007).
 * - department_name: a copy of the department name, kept in sync through
 *   DepartmentRenamed, so the directory can sort by department with cursor
 *   pagination and without joining another module's table (ADR-0008).
 * - deletion_reason: why the record was soft-deleted; cleared on restore.
 */
return new class extends Migration
{
    private const TABLE = 'employees';

    public function up(): void
    {
        Schema::table(self::TABLE, static function (Blueprint $table): void {
            $table->foreignUuid('user_id')->nullable()->after('department_id')->unique()->constrained('users')->nullOnDelete();
            $table->string('department_name', 150)->default('')->after('department_id')->comment('Copy of departments.name for sorting');
            $table->string('deletion_reason', 500)->nullable()->after('photo_mime_type');
            $table->index(['department_name', 'id']);
        });

        $departments = app(DepartmentDirectory::class);

        DB::table(self::TABLE)->whereNotNull('department_id')->distinct()->pluck('department_id')
            ->each(static fn (string $departmentId) => DB::table(self::TABLE)
                ->where('department_id', $departmentId)
                ->update(['department_name' => $departments->nameOf($departmentId) ?? '']));
    }

    public function down(): void
    {
        Schema::table(self::TABLE, static function (Blueprint $table): void {
            $table->dropIndex(['department_name', 'id']);
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['department_name', 'deletion_reason']);
        });
    }
};
