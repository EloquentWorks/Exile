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
        // Create the device fingerprints table with the specified columns and indexes
        Schema::create(config('exile.tables.device_fingerprints', 'exile_device_fingerprints'), function (Blueprint $table): void {
            // Create an auto-incrementing primary key column named 'id'
            $table->id();

            // Create polymorphic relationship columns for the 'fingerprintable' entity
            $table->morphs('fingerprintable');

            // Create a char column for storing the fingerprint hash with a length of 64 characters
            $table->char('fingerprint_hash', 64);

            // Create a char column for storing the last IP hash with a length of 64 characters, allowing null values and indexing it for efficient querying
            $table->char('last_ip_hash', 64)->nullable()->index();

            // Create a nullable string column for storing a label associated with the device fingerprint
            $table->string('label')->nullable();

            // Create a nullable JSON column for storing additional metadata related to the device fingerprint
            $table->json('metadata')->nullable();

            // Create timestamp columns for tracking the first and last time the device fingerprint was seen
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            
            // Create timestamp columns for tracking when the record was created and last updated
            $table->timestamps();
            
            // Create a unique index on the combination of 'fingerprintable_type', 'fingerprintable_id', and 'fingerprint_hash' to ensure uniqueness of device fingerprints
            $table->unique(['fingerprintable_type', 'fingerprintable_id', 'fingerprint_hash'], 'exile_unique_device_fingerprint');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void Returns nothing.
     */
    public function down(): void
    {
        // Drop the device fingerprints table if it exists
        Schema::dropIfExists(config('exile.tables.device_fingerprints', 'exile_device_fingerprints'));
    }
};
