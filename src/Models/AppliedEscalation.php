<?php

namespace EloquentWorks\Exile\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $escalatable_type
 * @property int|string $escalatable_id
 * @property int $threshold_points
 * @property string $action
 * @property array<string, mixed>|null $metadata
 * @property-read Model $escalatable
 */
class AppliedEscalation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'escalatable_type',
        'escalatable_id',
        'threshold_points',
        'action',
        'metadata',
    ];

    /**
     * Get the table associated with the model.
     *
     * @return string Return the table name for this model.
     */
    public function getTable(): string
    {
        // Get the table name for the AppliedEscalation model from the
        // configuration, defaulting to 'exile_escalations' if not set.
        return (string) config(
            'exile.tables.escalations',
            'exile_escalations'
        );
    }

    /**
     * Get the parent escalatable model (e.g., Ban, Restriction, Strike, Warning).
     *
     * @return MorphTo<Model, $this> Returns the polymorphic relationship to the escalatable model.
     */
    public function escalatable(): MorphTo
    {
        // Define a polymorphic relationship to the parent model that this escalation is applied to.
        return $this->morphTo();
    }

    /**
     * Get the casts for the model's attributes.
     *
     * @return array<string, string> Returns the attribute casts for the model.
     */
    protected function casts(): array
    {
        // Define the attribute casts for the model's properties, ensuring proper data types.
        return [
            'threshold_points' => 'integer',
            'metadata' => 'array',
        ];
    }
}
