<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('exile.tables.device_fingerprints', 'exile_device_fingerprints'), function (Blueprint $table): void {
            $table->id();
            $table->morphs('fingerprintable');
            $table->char('fingerprint_hash', 64);
            $table->char('last_ip_hash', 64)->nullable()->index();
            $table->string('label')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamps();
            $table->unique(['fingerprintable_type', 'fingerprintable_id', 'fingerprint_hash'], 'exile_unique_device_fingerprint');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('exile.tables.device_fingerprints', 'exile_device_fingerprints'));
    }
};
