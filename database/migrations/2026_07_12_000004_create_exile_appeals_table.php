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
        // Create the appeals table with the specified columns and indexes
        Schema::create(config('exile.tables.appeals', 'exile_appeals'), function (Blueprint $table): void {
            // Create an auto-incrementing primary key column named 'id'
            $table->id();

            // Create a foreign key column for the 'ban_id' that references the 'id' column in the bans table, with cascade on delete
            $table->foreignId('ban_id')->constrained(config('exile.tables.bans', 'exile_bans'))->cascadeOnDelete();

            // Create polymorphic relationship columns for the 'appellant' entity
            $table->morphs('appellant');

            // Create a string column for storing the status of the appeal with a default value of 'pending' and an index for efficient querying
            $table->string('status', 30)->default('pending')->index();

            // Create a text column for storing the appeal message and a nullable text column for storing the response to the appeal
            $table->text('message');
            $table->text('response')->nullable();

            // Create nullable polymorphic relationship columns for the 'reviewed_by' entity and a nullable timestamp column for when the appeal was reviewed
            $table->nullableMorphs('reviewed_by');

            // Create a nullable timestamp column for when the appeal was reviewed
            $table->timestamp('reviewed_at')->nullable();

            // Create a nullable timestamp column for when the appeal was created and updated
            $table->timestamps();

            // Create a composite index on the 'ban_id' and 'status' columns for efficient querying of appeals by ban and status
            $table->index(['ban_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void Returns nothing.
     */
    public function down(): void
    {
        // Drop the appeals table if it exists
        Schema::dropIfExists(config('exile.tables.appeals', 'exile_appeals'));
    }
};
