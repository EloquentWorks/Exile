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
        // Create the bans table with the specified columns and indexes
        Schema::create(config('exile.tables.bans', 'exile_bans'), function (Blueprint $table): void {
            // Create an auto-incrementing primary key column named 'id'
            $table->id();

            // Create a string column named 'type' with a maximum length of 40 characters
            $table->string('type', 40);

            // Create nullable polymorphic relationship columns for the 'bannable' entity
            $table->nullableMorphs('bannable');

            // Create a nullable text column for storing the IP address
            $table->text('ip_address')->nullable();

            // Create a nullable char column for storing the hashed IP address with a length of 64 characters and an index for efficient querying
            $table->char('ip_hash', 64)->nullable()->index();

            // Create a nullable text column for storing the CIDR notation of the IP address
            $table->text('cidr')->nullable();

            // Create a nullable char column for storing the hashed device identifier with a length of 64 characters and an index for efficient querying
            $table->char('device_hash', 64)->nullable()->index();

            // Create a nullable string column for storing the category of the ban with a maximum length of 80 characters and an index for efficient querying
            $table->string('category', 80)->nullable()->index();
            $table->string('reason', 500)->nullable();

            // Create a nullable text column for storing internal notes related to the ban
            $table->text('internal_notes')->nullable();

            // Create a nullable JSON column for storing additional metadata related to the ban
            $table->json('metadata')->nullable();

            // Create nullable polymorphic relationship columns for the 'banned_by' and 'revoked_by' entities
            $table->nullableMorphs('banned_by');
            $table->nullableMorphs('revoked_by');

            // Create nullable timestamp columns for the expiration and revocation of the ban, with indexes for efficient querying
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamp('expired_notified_at')->nullable();

            // Create a nullable timestamp column for the last time the ban was updated
            $table->timestamps();

            // Create a composite index on the 'bannable_type', 'bannable_id', 'revoked_at', and 'expires_at' columns for efficient querying of active bans
            $table->index(['bannable_type', 'bannable_id', 'revoked_at', 'expires_at'], 'exile_active_account_ban_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void Returns nothing.
     */
    public function down(): void
    {
        // Drop the bans table if it exists, using the configured table name or defaulting to 'exile_bans'.
        Schema::dropIfExists(config('exile.tables.bans', 'exile_bans'));
    }
};
