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
        // Create the restrictions table with the specified columns and indexes
        Schema::create(config('exile.tables.restrictions', 'exile_restrictions'), function (Blueprint $table): void {
            // Create an auto-incrementing primary key column named 'id'
            $table->id();

            // Create polymorphic relationship columns for the 'restrictable' entity
            $table->morphs('restrictable');
            
            // Create a string column named 'type' with a maximum length of 40 characters and an index for efficient querying
            $table->string('type', 40)->index();

            // Create a nullable string column for the reason of the restriction with a maximum length of 500 characters
            $table->string('reason', 500)->nullable();

            // Create a nullable text column for internal notes related to the restriction
            $table->text('internal_notes')->nullable();

            // Create a nullable JSON column for storing additional metadata related to the restriction
            $table->json('metadata')->nullable();

            // Create nullable polymorphic relationship columns for the 'issued_by' and 'revoked_by' entities
            $table->nullableMorphs('issued_by');
            $table->nullableMorphs('revoked_by');
            
            // Create nullable timestamp columns for the expiration and revocation of the restriction, with indexes for efficient querying
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamp('expired_notified_at')->nullable();

            // Create a nullable timestamp column for the last time the restriction was updated
            $table->timestamps();
            
            // Create a composite index on the 'restrictable_type', 'restrictable_id', 'type', 'revoked_at', and 'expires_at' columns for efficient querying of active restrictions
            $table->index(['restrictable_type', 'restrictable_id', 'type', 'revoked_at', 'expires_at'], 'exile_active_restriction_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void Returns nothing.
     */
    public function down(): void
    {
        // Drop the restrictions table if it exists, using the configured table name or defaulting to 'exile_restrictions'.
        Schema::dropIfExists(config('exile.tables.restrictions', 'exile_restrictions'));
    }
};
