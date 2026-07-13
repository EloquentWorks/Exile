<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('exile.tables.appeals', 'exile_appeals'), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ban_id')->constrained(config('exile.tables.bans', 'exile_bans'))->cascadeOnDelete();
            $table->morphs('appellant');
            $table->string('status', 30)->default('pending')->index();
            $table->text('message');
            $table->text('response')->nullable();
            $table->nullableMorphs('reviewed_by');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['ban_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('exile.tables.appeals', 'exile_appeals'));
    }
};
