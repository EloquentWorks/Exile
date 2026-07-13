<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('exile.tables.restrictions', 'exile_restrictions'), function (Blueprint $table): void {
            $table->id();
            $table->morphs('restrictable');
            $table->string('type', 40)->index();
            $table->string('reason', 500)->nullable();
            $table->text('internal_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->nullableMorphs('issued_by');
            $table->nullableMorphs('revoked_by');
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamp('expired_notified_at')->nullable();
            $table->timestamps();
            $table->index(['restrictable_type', 'restrictable_id', 'type', 'revoked_at', 'expires_at'], 'exile_active_restriction_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('exile.tables.restrictions', 'exile_restrictions'));
    }
};
