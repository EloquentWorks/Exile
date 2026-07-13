<?php

namespace EloquentWorks\Exile\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $action
 * @property array<string, mixed>|null $context
 * @property-read Model|null $subject
 * @property-read Model|null $actor
 */
class ModerationAction extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'action',
        'subject_type',
        'subject_id',
        'actor_type',
        'actor_id',
        'context',
    ];

    /**
     * Get the table name for the model.
     *
     * @return string Return the table name for this model.
     */
    public function getTable(): string
    {
        // Get the table name from the configuration, defaulting to 'exile_actions' if not set.
        return (string) config('exile.tables.actions', 'exile_actions');
    }

    /**
     * Get the subject of the moderation action.
     *
     * @return MorphTo<Model, $this> Returns the polymorphic relationship to the subject of the moderation action.
     */
    public function subject(): MorphTo
    {
        // Return the polymorphic relationship to the subject of the moderation action.
        return $this->morphTo();
    }

    /**
     * Get the actor of the moderation action.
     *
     * @return MorphTo<Model, $this> Returns the polymorphic relationship to the actor of the moderation action.
     */
    public function actor(): MorphTo
    {
        // Return the polymorphic relationship to the actor of the moderation action.
        return $this->morphTo();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        // Return the casts for the model attributes.
        return ['context' => 'array'];
    }
}
