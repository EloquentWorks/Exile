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
        // Create the escalations table with the specified columns and indexes
        Schema::create(
            config(
                'exile.tables.escalations',
                'exile_escalations'
            ),
            function (Blueprint $table): void {
                // Create an auto-incrementing primary key column named 'id'
                $table->id();

                // Create polymorphic relationship columns for the 'escalatable' entity
                $table->morphs('escalatable');

                // Create an unsigned integer column for the threshold points
                $table->unsignedInteger(
                    'threshold_points'
                );

                // Create a string column for the action to be taken when the threshold is reached, with a maximum length of 32 characters
                $table->string('action', 32);

                // Create a JSON column for storing additional metadata, which can be nullable

                $table->json('metadata')->nullable();
                // Create timestamp columns for tracking creation and update times
                $table->timestamps();

                // Create a unique index on the combination of 'escalatable_type', 'escalatable_id', and 'threshold_points' to ensure uniqueness of escalations
                $table->unique(
                    [
                        'escalatable_type',
                        'escalatable_id',
                        'threshold_points',
                    ],
                    'exile_escalation_threshold_unique'
                );
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void Returns nothing.
     */
    public function down(): void
    {
        // Drop the escalations table if it exists
        Schema::dropIfExists(
            config(
                'exile.tables.escalations',
                'exile_escalations'
            )
        );
    }
};
