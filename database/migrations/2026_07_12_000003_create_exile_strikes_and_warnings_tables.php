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
        // Create the strikes table with the specified columns and indexes
        Schema::create(config('exile.tables.strikes', 'exile_strikes'), function (Blueprint $table): void {
            // Create an auto-incrementing primary key column named 'id'
            $table->id();

            // Create polymorphic relationship columns for the 'strikeable' entity
            $table->morphs('strikeable');

            // Create an unsigned integer column for the points associated with the strike, defaulting to 1
            $table->unsignedInteger('points')->default(1);

            // Create a string column for the severity of the strike with a maximum length of 30 characters, defaulting to 'medium'
            $table->string('category', 80)->nullable()->index();
            $table->string('reason', 500);
            
            // Create a text column for internal notes related to the strike, allowing null values
            $table->json('metadata')->nullable();

            // Create nullable polymorphic relationship columns for the 'issued_by' and 'revoked_by' entities
            $table->nullableMorphs('issued_by');
            $table->nullableMorphs('revoked_by');
            
            // Create nullable timestamp columns for the expiration and revocation of the strike, with indexes for efficient querying
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable()->index();

            // Create a nullable timestamp column for the last time the strike was updated
            $table->timestamps();
        });

        // Create the warnings table with the specified columns and indexes
        Schema::create(config('exile.tables.warnings', 'exile_warnings'), function (Blueprint $table): void {
            // Create an auto-incrementing primary key column named 'id'
            $table->id();

            // Create polymorphic relationship columns for the 'warnable' entity
            $table->morphs('warnable');

            // Create a string column for the severity of the warning with a maximum length of 30 characters, defaulting to 'medium'
            $table->string('severity', 30)->default('medium')->index();

            // Create a string column for the category of the warning with a maximum length of 80 characters, allowing null values
            $table->string('category', 80)->nullable()->index();
            
            // Create a string column for the reason of the warning with a maximum length of 500 characters
            $table->string('reason', 500);

            // Create a text column for internal notes related to the warning, allowing null values
            $table->text('internal_notes')->nullable();
            
            // Create a nullable JSON column for storing additional metadata related to the warning
            $table->json('metadata')->nullable();
            
            // Create nullable polymorphic relationship columns for the 'issued_by' entity
            $table->nullableMorphs('issued_by');
            
            // Create a nullable timestamp column for when the warning was acknowledged
            $table->timestamp('acknowledged_at')->nullable();
            
            // Create a nullable timestamp column for the last time the warning was updated
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
        // Drop the warnings and strikes tables if they exist
        Schema::dropIfExists(config('exile.tables.warnings', 'exile_warnings'));
        Schema::dropIfExists(config('exile.tables.strikes', 'exile_strikes'));
    }
};
