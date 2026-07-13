<?php

namespace EloquentWorks\Exile\Models;

use EloquentWorks\Exile\Enums\AppealStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $ban_id
 * @property AppealStatus $status
 * @property string $message
 * @property string|null $response
 * @property Carbon|null $reviewed_at
 * @property-read Ban $ban
 * @property-read Model $appellant
 * @property-read Model|null $reviewedBy
 */
class BanAppeal extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'ban_id',
        'appellant_type',
        'appellant_id',
        'status',
        'message',
        'response',
        'reviewed_by_type',
        'reviewed_by_id',
        'reviewed_at',
    ];

    /**
     * Get the table associated with the model.
     *
     * @return string Returns the table name for this model.
     */
    public function getTable(): string
    {
        // Return the table name for this model, defaulting to 'exile_appeals' if not configured.
        return (string) config('exile.tables.appeals', 'exile_appeals');
    }

    /**
     * Get the ban associated with this appeal.
     *
     * @return BelongsTo<Ban, $this> Returns the relationship to the associated ban.
     */
    public function ban(): BelongsTo
    {
        /** @var class-string<Ban> $model */
        $model = config('exile.models.ban', Ban::class);

        /** @var BelongsTo<Ban, $this> $relation */
        $relation = $this->belongsTo($model, 'ban_id');

        // Return the relationship to the associated ban.
        return $relation;
    }

    /**
     * Get the appellant associated with this appeal.
     *
     * @return MorphTo<Model, $this> Returns the relationship to the appellant.
     */
    public function appellant(): MorphTo
    {
        // Return the polymorphic relationship to the appellant.
        return $this->morphTo();
    }

    /**
     * Get the user who reviewed this appeal.
     *
     * @return MorphTo<Model, $this> Returns the relationship to the reviewer.
     */
    public function reviewedBy(): MorphTo
    {
        // Return the polymorphic relationship to the reviewer.
        return $this->morphTo(__FUNCTION__, 'reviewed_by_type', 'reviewed_by_id');
    }

    /**
     * Get the evidence associated with this appeal.
     *
     * @return MorphMany<Evidence, $this> Returns the relationship to the associated evidence.
     */
    public function evidence(): MorphMany
    {
        /** @var class-string<Evidence> $model */
        $model = config('exile.models.evidence', Evidence::class);

        /** @var MorphMany<Evidence, $this> $relation */
        $relation = $this->morphMany($model, 'evidenceable');

        // Return the relationship to the associated evidence.
        return $relation;
    }

    /**
     * Check if the appeal is pending.
     *
     * @return bool Returns true if the appeal status is pending, false otherwise.
     */
    public function isPending(): bool
    {
        // Check if the appeal status is pending.
        return $this->status === AppealStatus::Pending;
    }

    /**
     * Get the casts for the model's attributes.
     *
     * @return array<string, string> An array of attribute casts.
     */
    protected function casts(): array
    {
        return [
            'status' => AppealStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }
}
