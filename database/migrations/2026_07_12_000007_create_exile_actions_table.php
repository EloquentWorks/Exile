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
        // Create the actions table with the specified columns and indexes
        Schema::create(config('exile.tables.actions', 'exile_actions'), function (Blueprint $table): void {
            // Create an auto-incrementing primary key column named 'id'
            $table->id();

            // Create a string column named 'action' with a maximum length of 100 characters and an index for efficient querying
            $table->string('action', 100)->index();

            // Create nullable polymorphic relationship columns for the 'subject' and 'actor' entities
            $table->nullableMorphs('subject');
            $table->nullableMorphs('actor');
            
            // Create a nullable JSON column for storing additional context related to the action
            $table->json('context')->nullable();
            
            // Create a nullable timestamp column for the action's occurrence time
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
        // Drop the actions table if it exists
        Schema::dropIfExists(config('exile.tables.actions', 'exile_actions'));
    }
};
