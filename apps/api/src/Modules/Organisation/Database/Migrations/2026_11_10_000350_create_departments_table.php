<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code', 20)->unique();
            $table->string('name', 150)->index();
            $table->string('description', 500)->nullable();
            $table->uuid('parent_id')->nullable()->index();
            $table->unsignedInteger('version')->default(1)->comment('Optimistic lock (ETag / If-Match)');
            $table->timestampsTz();
        });

        // Self-reference: added once the primary key exists.
        Schema::table('departments', static function (Blueprint $table): void {
            $table->foreign('parent_id')->references('id')->on('departments')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
