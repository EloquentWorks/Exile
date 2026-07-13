<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('exile.tables.strikes', 'exile_strikes'), function (Blueprint $table): void {
            $table->id();
            $table->morphs('strikeable');
            $table->unsignedInteger('points')->default(1);
            $table->string('category', 80)->nullable()->index();
            $table->string('reason', 500);
            $table->json('metadata')->nullable();
            $table->nullableMorphs('issued_by');
            $table->nullableMorphs('revoked_by');
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create(config('exile.tables.warnings', 'exile_warnings'), function (Blueprint $table): void {
            $table->id();
            $table->morphs('warnable');
            $table->string('severity', 30)->default('medium')->index();
            $table->string('category', 80)->nullable()->index();
            $table->string('reason', 500);
            $table->text('internal_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->nullableMorphs('issued_by');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('exile.tables.warnings', 'exile_warnings'));
        Schema::dropIfExists(config('exile.tables.strikes', 'exile_strikes'));
    }
};
