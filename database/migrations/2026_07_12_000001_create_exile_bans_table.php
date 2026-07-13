<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('exile.tables.bans', 'exile_bans'), function (Blueprint $table): void {
            $table->id();
            $table->string('type', 40);
            $table->nullableMorphs('bannable');
            $table->text('ip_address')->nullable();
            $table->char('ip_hash', 64)->nullable()->index();
            $table->text('cidr')->nullable();
            $table->char('device_hash', 64)->nullable()->index();
            $table->string('category', 80)->nullable()->index();
            $table->string('reason', 500)->nullable();
            $table->text('internal_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->nullableMorphs('banned_by');
            $table->nullableMorphs('revoked_by');
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamp('expired_notified_at')->nullable();
            $table->timestamps();
            $table->index(['bannable_type', 'bannable_id', 'revoked_at', 'expires_at'], 'exile_active_account_ban_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('exile.tables.bans', 'exile_bans'));
    }
};
