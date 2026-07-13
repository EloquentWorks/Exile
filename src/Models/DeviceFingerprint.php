<?php

namespace EloquentWorks\Exile\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $fingerprint_hash
 * @property string|null $last_ip_hash
 * @property string|null $label
 * @property array<string, mixed>|null $metadata
 * @property Carbon $first_seen_at
 * @property Carbon $last_seen_at
 * @property-read Model|null $fingerprintable
 */
class DeviceFingerprint extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'fingerprintable_type',
        'fingerprintable_id',
        'fingerprint_hash',
        'last_ip_hash',
        'label',
        'metadata',
        'first_seen_at',
        'last_seen_at',
    ];

    /**
     * Get the table associated with the model.
     *
     * @return string Returns the table name for this model.
     */
    public function getTable(): string
    {
        // Return the table name from the configuration, defaulting to 'exile_device_fingerprints'.
        return (string) config('exile.tables.device_fingerprints', 'exile_device_fingerprints');
    }

    /**
     * Get the parent fingerprintable model (user, account, etc.).
     *
     * @return MorphTo<Model, $this>
     */
    public function fingerprintable(): MorphTo
    {
        // Return the polymorphic relationship to the fingerprintable model.
        return $this->morphTo();
    }

    /**
     * Get the casts for the model's attributes.
     *
     * @return array<string, string> An array of attribute casts.
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }
}
