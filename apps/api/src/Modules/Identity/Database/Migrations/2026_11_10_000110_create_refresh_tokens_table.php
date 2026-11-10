<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refresh_tokens', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('family_id')->index()->comment('Session id shared by every rotation of one login');
            $table->char('token_hash', 64)->unique()->comment('SHA-256 of the opaque token');
            $table->timestampTz('expires_at')->index();
            $table->timestampTz('revoked_at')->nullable();
            $table->uuid('replaced_by_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestampsTz();

            $table->index(['user_id', 'revoked_at']);
        });

        Schema::table('refresh_tokens', static function (Blueprint $table): void {
            $table->foreign('replaced_by_id')->references('id')->on('refresh_tokens')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refresh_tokens');
    }
};
