<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('exile.tables.evidence', 'exile_evidence'), function (Blueprint $table): void {
            $table->id();
            $table->morphs('evidenceable');
            $table->string('disk');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->json('metadata')->nullable();
            $table->nullableMorphs('uploaded_by');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('exile.tables.evidence', 'exile_evidence'));
    }
};
