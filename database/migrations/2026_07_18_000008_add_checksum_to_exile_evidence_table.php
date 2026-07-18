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
        // Add a new column 'checksum_sha256' to the evidence table for storing SHA-256 checksums
        Schema::table(
            config('exile.tables.evidence', 'exile_evidence'),
            function (Blueprint $table): void {
                // Add a nullable char column named 'checksum_sha256' with a length of 64 characters and an index for efficient querying
                $table->char('checksum_sha256', 64)
                    ->nullable()
                    ->index();
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
        // Remove the 'checksum_sha256' column from the evidence table
        Schema::table(
            config('exile.tables.evidence', 'exile_evidence'),
            function (Blueprint $table): void {
                // Drop the 'checksum_sha256' column if it exists
                $table->dropColumn('checksum_sha256');
            }
        );
    }
};
