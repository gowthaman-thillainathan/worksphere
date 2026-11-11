<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const EMPLOYEE_SEQUENCE = 'employees';

    public function up(): void
    {
        Schema::create('employees', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('employee_number', 30)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('preferred_name', 100)->nullable();
            $table->string('work_email')->index();
            $table->text('personal_email')->nullable()->comment('encrypted');
            $table->text('phone')->nullable()->comment('encrypted');
            $table->text('date_of_birth')->nullable()->comment('encrypted');
            $table->string('job_title', 150);
            $table->string('employment_type', 20)->index();
            $table->string('status', 20)->index();
            $table->date('hire_date')->index();
            $table->date('termination_date')->nullable();
            $table->uuid('manager_id')->nullable()->index();
            $table->string('photo_path')->nullable();
            $table->string('photo_mime_type', 50)->nullable();
            $table->unsignedInteger('version')->default(1)->comment('Optimistic lock (ETag / If-Match)');
            $table->timestampsTz();
            $table->softDeletesTz()->index();

            $table->index(['last_name', 'first_name', 'id']);
            $table->index(['created_at', 'id']);
        });

        // Self-reference: added once the primary key exists.
        Schema::table('employees', static function (Blueprint $table): void {
            $table->foreign('manager_id')->references('id')->on('employees')->restrictOnDelete();
        });

        Schema::create('employee_number_sequences', static function (Blueprint $table): void {
            $table->string('name', 50)->primary();
            $table->unsignedBigInteger('last_value')->default(0);
        });

        DB::table('employee_number_sequences')->insert(['name' => self::EMPLOYEE_SEQUENCE, 'last_value' => 0]);
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_number_sequences');
        Schema::dropIfExists('employees');
    }
};
