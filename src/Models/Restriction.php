<?php

namespace EloquentWorks\Exile\Models;

use EloquentWorks\Exile\Enums\RestrictionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Represents a restriction applied to a user or entity, which may have a type, reason, internal notes, metadata,
 * expiration date, and revocation information.
 *
 * @property int $id
 * @property string $restrictable_type
 * @property int|string $restrictable_id
 * @property RestrictionType $type
 * @property string|null $reason
 * @property string|null $internal_notes
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $expires_at
 * @property Carbon|null $revoked_at
 * @property Carbon|null $expired_notified_at
 * @property-read Model $restrictable
 * @property-read Model|null $issuedBy
 * @property-read Model|null $revokedBy
 *
 * @method static Builder<static> active()
 * @method static Builder<static> ofType(RestrictionType|string $type)
 */
class Restriction extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'restrictable_type',
        'restrictable_id',
        'type',
        'reason',
        'internal_notes',
        'metadata',
        'issued_by_type',
        'issued_by_id',
        'revoked_by_type',
        'revoked_by_id',
        'expires_at',
        'revoked_at',
        'expired_notified_at',
    ];

    /**
     * Get the table name for the model.
     *
     * @return string Return the table name for this model.
     */
    public function getTable(): string
    {
        return (string) config('exile.tables.restrictions', 'exile_restrictions');
    }

    /**
     * Get the restrictable model that this restriction belongs to.
     *
     * @return MorphTo<Model, $this> Return the morph-to relationship for the restrictable model.
     */
    public function restrictable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the model that issued the restriction.
     *
     * @return MorphTo<Model, $this> Return the morph-to relationship for the model that issued the restriction.
     */
    public function issuedBy(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'issued_by_type', 'issued_by_id');
    }

    /**
     * Get the model that revoked the restriction.
     *
     * @return MorphTo<Model, $this> Return the morph-to relationship for the model that revoked the restriction.
     */
    public function revokedBy(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'revoked_by_type', 'revoked_by_id');
    }

    /**
     * Get the evidence associated with the restriction.
     *
     * @return MorphMany<Evidence, $this> Return the morph-many relationship for the evidence associated with the restriction.
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
     * Scope a query to only include active restrictions.
     *
     * @param  Builder<static>  $query  The query builder instance.
     * @return Builder<static> The modified query builder.
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
     * Scope a query to only include restrictions of a specific type.
     *
     * @param  Builder<static>  $query  The query builder instance.
     * @param  RestrictionType|string  $type  The type of restriction to filter by.
     * @return Builder<static> The modified query builder.
     */
    public function scopeOfType(Builder $query, RestrictionType|string $type): Builder
    {
        return $query->where('type', $type instanceof RestrictionType ? $type->value : $type);
    }

    /**
     * Determine if the restriction is currently active.
     *
     * @return bool Return true if the restriction is active, false otherwise.
     */
    public function isActive(): bool
    {
        return $this->revoked_at === null && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * Get the casts for the model attributes.
     *
     * @return array<string, string> Return the casts for the model attributes.
     */
    protected function casts(): array
    {
        return [
            'type' => RestrictionType::class,
            'metadata' => 'array',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'expired_notified_at' => 'datetime',
        ];
    }
}
