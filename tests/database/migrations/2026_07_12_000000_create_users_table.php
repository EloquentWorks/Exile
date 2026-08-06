<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create the 'users' table with the specified columns.
        Schema::create('users', function (Blueprint $table): void {
            // Define the columns for the 'users' table.
            $table->id();

            // Add a 'name' column of type string.
            $table->string('name');

            // Add an 'email' column of type string that can be null.
            $table->string('email')->nullable();

            // Add a 'password' column of type string that can be null.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the 'users' table if it exists.
        Schema::dropIfExists('users');
    }
};
