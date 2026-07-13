<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void Returns nothing.
     */
    public function up(): void
    {
        // Create the evidence table with the specified columns and indexes
        Schema::create(config('exile.tables.evidence', 'exile_evidence'), function (Blueprint $table): void {
            // Create an auto-incrementing primary key column named 'id'
            $table->id();

            // Create polymorphic relationship columns for the 'evidenceable' entity
            $table->morphs('evidenceable');

            // Create a string column for the disk name where the evidence is stored
            $table->string('disk');

            // Create a string column for the path of the evidence file
            $table->string('path');

            // Create a string column for the original name of the evidence file, which can be nullable
            $table->string('original_name')->nullable();

            // Create a string column for the MIME type of the evidence file, which can be nullable
            $table->string('mime_type')->nullable();

            // Create an unsigned big integer column for the size of the evidence file in bytes, which can be nullable
            $table->unsignedBigInteger('size_bytes')->nullable();

            // Create a JSON column for storing additional metadata related to the evidence, which can be nullable
            $table->json('metadata')->nullable();

            // Create nullable polymorphic relationship columns for the entity that uploaded the evidence
            $table->nullableMorphs('uploaded_by');

            // Create a nullable timestamp column for the time when the evidence was uploaded
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void Returns nothing.
     */
    public function down(): void
    {
        // Drop the evidence table if it exists
        Schema::dropIfExists(config('exile.tables.evidence', 'exile_evidence'));
    }
};
