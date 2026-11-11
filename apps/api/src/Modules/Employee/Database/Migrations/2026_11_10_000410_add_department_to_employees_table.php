<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Expand migration: nullable, so existing rows and older code keep working.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', static function (Blueprint $table): void {
            $table->foreignUuid('department_id')->nullable()->after('manager_id')->index()->constrained('departments')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employees', static function (Blueprint $table): void {
            $table->dropConstrainedForeignId('department_id');
        });
    }
};
