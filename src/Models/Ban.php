<?php

namespace EloquentWorks\Exile\Models;

use EloquentWorks\Exile\Enums\BanType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property BanType $type
 * @property string|null $bannable_type
 * @property int|string|null $bannable_id
 * @property string|null $ip_address
 * @property string|null $ip_hash
 * @property string|null $cidr
 * @property string|null $device_hash
 * @property string|null $category
 * @property string|null $reason
 * @property string|null $internal_notes
 * @property array<string, mixed>|null $metadata
 * @property string|null $banned_by_type
 * @property int|string|null $banned_by_id
 * @property string|null $revoked_by_type
 * @property int|string|null $revoked_by_id
 * @property Carbon|null $expires_at
 * @property Carbon|null $revoked_at
 * @property Carbon|null $expired_notified_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Model|null $bannable
 * @property-read Model|null $bannedBy
 * @property-read Model|null $revokedBy
 * @property-read Collection<int, BanAppeal> $appeals
 * @property-read Collection<int, Evidence> $evidence
 *
 * @method static Builder<static> active()
 * @method static Builder<static> expired()
 * @method static Builder<static> revoked()
 * @method static Builder<static> permanent()
 */
class Ban extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'type',
        'bannable_type',
        'bannable_id',
        'ip_address',
        'ip_hash',
        'cidr',
        'device_hash',
        'category',
        'reason',
        'internal_notes',
        'metadata',
        'banned_by_type',
        'banned_by_id',
        'revoked_by_type',
        'revoked_by_id',
        'expires_at',
        'revoked_at',
        'expired_notified_at',
    ];

    /**
     * Get the table associated with the model.
     *
     * @return string Returns the table name for this model.
     */
    public function getTable(): string
    {
        // Return the table name for this model, which is configurable via the 'exile.tables.bans' configuration option, defaulting to 'exile_bans'.
        return (string) config('exile.tables.bans', 'exile_bans');
    }

    /**
     * Get the bannable entity associated with this ban.
     *
     * @return MorphTo<Model, $this> Returns the polymorphic relationship to the bannable entity.
     */
    public function bannable(): MorphTo
    {
        // Return the polymorphic relationship to the bannable entity.
        return $this->morphTo();
    }

    /**
     * Get the entity that issued the ban.
     *
     * @return MorphTo<Model, $this> Returns the polymorphic relationship to the entity that issued the ban.
     */
    public function bannedBy(): MorphTo
    {
        // Return the polymorphic relationship to the entity that issued the ban.
        return $this->morphTo(__FUNCTION__, 'banned_by_type', 'banned_by_id');
    }

    /**
     * Get the entity that revoked the ban.
     *
     * @return MorphTo<Model, $this> Returns the polymorphic relationship to the entity that revoked the ban.
     */
    public function revokedBy(): MorphTo
    {
        // Return the polymorphic relationship to the entity that revoked the ban.
        return $this->morphTo(__FUNCTION__, 'revoked_by_type', 'revoked_by_id');
    }

    /**
     * Get the appeals associated with this ban.
     *
     * @return HasMany<BanAppeal, $this> Returns the relationship to the ban appeals.
     */
    public function appeals(): HasMany
    {
        /** @var class-string<BanAppeal> $model */
        $model = config('exile.models.appeal', BanAppeal::class);

        /** @var HasMany<BanAppeal, $this> $relation */
        $relation = $this->hasMany($model, 'ban_id');

        // Return the relationship to the ban appeals.
        return $relation;
    }

    /**
     * Get the evidence associated with this ban.
     *
     * @return MorphMany<Evidence, $this> Returns the polymorphic relationship to the evidence.
     */
    public function evidence(): MorphMany
    {
        /** @var class-string<Evidence> $model */
        $model = config('exile.models.evidence', Evidence::class);

        /** @var MorphMany<Evidence, $this> $relation */
        $relation = $this->morphMany($model, 'evidenceable');

        // Return the polymorphic relationship to the evidence.
        return $relation;
    }

    /**
     * Scope a query to only include active bans.
     *
     * @param  Builder<static>  $query  The query builder instance.
     * @return Builder<static> The modified query builder.
     */
    public function scopeActive(Builder $query): Builder
    {
        // Scope a query to only include active bans.
        return $query
            ->whereNull('revoked_at')
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope a query to only include expired bans.
     *
     * @param  Builder<static>  $query  The query builder instance.
     * @return Builder<static> The modified query builder.
     */
    public function scopeExpired(Builder $query): Builder
    {
        // Scope a query to only include expired bans.
        return $query
            ->whereNull('revoked_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    /**
     * Scope a query to only include revoked bans.
     *
     * @param  Builder<static>  $query  The query builder instance.
     * @return Builder<static> The modified query builder.
     */
    public function scopeRevoked(Builder $query): Builder
    {
        // Scope a query to only include revoked bans.
        return $query->whereNotNull('revoked_at');
    }

    /**
     * Scope a query to only include permanent bans.
     *
     * @param  Builder<static>  $query  The query builder instance.
     * @return Builder<static> The modified query builder.
     */
    public function scopePermanent(Builder $query): Builder
    {
        // Scope a query to only include permanent bans.
        return $query->whereNull('expires_at');
    }

    /**
     * Determine if the ban is currently active.
     *
     * @return bool True if the ban is active, false otherwise.
     */
    public function isActive(): bool
    {
        // Determine if the ban is currently active by checking if it has not been revoked and either has no expiration date or the expiration date is in the future.
        return $this->revoked_at === null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * Determine if the ban has expired.
     *
     * @return bool True if the ban has expired, false otherwise.
     */
    public function isExpired(): bool
    {
        // Determine if the ban has expired by checking if the expiration date is in the past.
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Determine if the ban is permanent.
     *
     * @return bool True if the ban is permanent, false otherwise.
     */
    public function isPermanent(): bool
    {
        // Determine if the ban is permanent by checking if it has no expiration date.
        return $this->expires_at === null;
    }

    /**
     * Determine if the ban has been revoked.
     *
     * @return bool True if the ban has been revoked, false otherwise.
     */
    public function isRevoked(): bool
    {
        // Determine if the ban has been revoked by checking if the revoked_at attribute is not null.
        return $this->revoked_at !== null;
    }

    /**
     * Get the casts for the model's attributes.
     *
     * @return array<string, string> An array of attribute casts.
     */
    protected function casts(): array
    {
        return [
            'type' => BanType::class,
            'ip_address' => 'encrypted',
            'cidr' => 'encrypted',
            'metadata' => 'array',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'expired_notified_at' => 'datetime',
        ];
    }
}
