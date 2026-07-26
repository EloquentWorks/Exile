<?php

namespace EloquentWorks\Exile\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Represents a strike issued to a user or entity, which may have points, a category, a reason, and optional metadata.
 *
 * @property int $id
 * @property string $strikeable_type
 * @property int|string $strikeable_id
 * @property int $points
 * @property string|null $category
 * @property string $reason
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $expires_at
 * @property Carbon|null $revoked_at
 * @property-read Model $strikeable
 *
 * @method static Builder<static> active()
 */
class Strike extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'strikeable_type',
        'strikeable_id',
        'points',
        'category',
        'reason',
        'metadata',
        'issued_by_type',
        'issued_by_id',
        'revoked_by_type',
        'revoked_by_id',
        'expires_at',
        'revoked_at',
    ];

    /**
     * Get the table name for the model.
     *
     * @return string Return the table name for this model.
     */
    public function getTable(): string
    {
        return (string) config('exile.tables.strikes', 'exile_strikes');
    }

    /**
     * Get the model that this strike is associated with.
     *
     * @return MorphTo<Model, $this> Returns a polymorphic relationship to the model that this strike is associated with.
     */
    public function strikeable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the model that issued this strike.
     *
     * @return MorphTo<Model, $this> Returns a polymorphic relationship to the model that issued this strike.
     */
    public function issuedBy(): MorphTo
    {
        // Return a polymorphic relationship to the model that issued this strike.
        return $this->morphTo(__FUNCTION__, 'issued_by_type', 'issued_by_id');
    }

    /**
     * Get the model that revoked this strike.
     *
     * @return MorphTo<Model, $this> Returns a polymorphic relationship to the model that revoked this strike.
     */
    public function revokedBy(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'revoked_by_type', 'revoked_by_id');
    }

    /**
     * Get the evidence associated with this strike.
     *
     * @return MorphMany<Evidence, $this> Returns a polymorphic relationship to the evidence associated with this strike.
     */
    public function evidence(): MorphMany
    {
        /** @var class-string<Evidence> $model */
        $model = config('exile.models.evidence', Evidence::class);

        /** @var MorphMany<Evidence, $this> $relation */
        $relation = $this->morphMany($model, 'evidenceable');

        return $relation;
    }

    /**
     * Scope a query to only include active strikes (not revoked and not expired).
     *
     * @param  Builder<Strike>  $query
     * @return Builder<Strike>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('revoked_at')
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Determine if the strike is currently active (not revoked and not expired).
     *
     * @return bool Returns true if the strike is active, false otherwise.
     */
    public function isActive(): bool
    {
        return $this->revoked_at === null && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * Get the casts for the model's attributes.
     *
     * @return array<string, string> Returns an array of attribute casts for the model.
     */
    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'metadata' => 'array',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }
}
