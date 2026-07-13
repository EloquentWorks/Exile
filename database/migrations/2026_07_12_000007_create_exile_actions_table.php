<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('exile.tables.actions', 'exile_actions'), function (Blueprint $table): void {
            $table->id();
            $table->string('action', 100)->index();
            $table->nullableMorphs('subject');
            $table->nullableMorphs('actor');
            $table->json('context')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('exile.tables.actions', 'exile_actions'));
    }
};
