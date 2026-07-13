<?php

namespace EloquentWorks\Exile\Models;

use EloquentWorks\Exile\Enums\WarningSeverity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property WarningSeverity $severity
 * @property string|null $category
 * @property string $reason
 * @property string|null $internal_notes
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $acknowledged_at
 * @property-read Model $warnable
 */
class Warning extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'warnable_type',
        'warnable_id',
        'severity',
        'category',
        'reason',
        'internal_notes',
        'metadata',
        'issued_by_type',
        'issued_by_id',
        'acknowledged_at',
    ];

    /**
     * Get the table name for the model.
     *
     * @return string Return the table name for this model.
     */
    public function getTable(): string
    {
        // Return the table name for this model, using the configuration value 'exile.tables.warnings' if it exists, or defaulting to 'exile_warnings' if not.
        return (string) config('exile.tables.warnings', 'exile_warnings');
    }

    /**
     * Get the model that this warning is associated with.
     *
     * @return MorphTo<Model, $this> Returns a polymorphic relationship to the model that this warning is associated with.
     */
    public function warnable(): MorphTo
    {
        // Return a polymorphic relationship to the model that this warning is associated with, using the 'warnable_type' and 'warnable_id' columns to determine the related model.
        return $this->morphTo();
    }

    /**
     * Get the model that issued this warning.
     *
     * @return MorphTo<Model, $this> Returns a polymorphic relationship to the model that issued this warning.
     */
    public function issuedBy(): MorphTo
    {
        // Return a polymorphic relationship to the model that issued this warning, using the 'issued_by_type' and 'issued_by_id' columns to determine the related model.
        return $this->morphTo(__FUNCTION__, 'issued_by_type', 'issued_by_id');
    }

    /**
     * Get the evidence associated with this warning.
     *
     * @return MorphMany<Evidence, $this> Returns a polymorphic relationship to the evidence associated with this warning.
     */
    public function evidence(): MorphMany
    {
        /** @var class-string<Evidence> $model */
        $model = config('exile.models.evidence', Evidence::class);

        /** @var MorphMany<Evidence, $this> $relation */
        $relation = $this->morphMany($model, 'evidenceable');

        // Return the polymorphic relationship to the evidence associated with this warning.
        return $relation;
    }

    /**
     * Acknowledge the warning.
     *
     * @return bool Returns true if the warning was successfully acknowledged, false otherwise.
     */
    public function acknowledge(): bool
    {
        // Acknowledge the warning by setting the acknowledged_at attribute to the current time.
        return $this->forceFill(['acknowledged_at' => now()])->save();
    }

    /**
     * Get the casts for the model's attributes.
     *
     * @return array<string, string> Returns an array of attribute casts for the model.
     */
    protected function casts(): array
    {
        // Return an array of attribute casts for the model.
        return [
            'severity' => WarningSeverity::class,
            'metadata' => 'array',
            'acknowledged_at' => 'datetime',
        ];
    }
}
