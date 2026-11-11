<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DEFAULT_CHAIN = 'global';

    public function up(): void
    {
        Schema::create('audit_events', static function (Blueprint $table): void {
            $table->id()->comment('Chain order');
            $table->timestampTz('occurred_at')->index();
            $table->string('actor_type', 20);
            $table->uuid('actor_id')->nullable()->index();
            $table->string('action', 100)->index();
            $table->string('subject_type', 100);
            $table->uuid('subject_id');
            $table->jsonb('changes');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->uuid('request_id')->nullable()->index();
            $table->char('prev_hash', 64)->nullable();
            $table->char('hash', 64)->unique();

            // No foreign keys on purpose: subjects may be anonymised or deleted,
            // their audit trail must survive (docs/04 §6).
            $table->index(['subject_type', 'subject_id', 'id']);
        });

        Schema::create('audit_chain_heads', static function (Blueprint $table): void {
            $table->string('chain', 50)->primary();
            $table->char('last_hash', 64)->nullable();
            $table->timestampTz('updated_at')->nullable();
        });

        DB::table('audit_chain_heads')->insert(['chain' => self::DEFAULT_CHAIN, 'last_hash' => null]);
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_chain_heads');
        Schema::dropIfExists('audit_events');
    }
};
