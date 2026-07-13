<?php

namespace EloquentWorks\Exile\Traits;

use DateTimeInterface;
use EloquentWorks\Exile\Enums\RestrictionType;
use EloquentWorks\Exile\Enums\WarningSeverity;
use EloquentWorks\Exile\Models\Ban;
use EloquentWorks\Exile\Models\DeviceFingerprint;
use EloquentWorks\Exile\Models\Restriction;
use EloquentWorks\Exile\Models\Strike;
use EloquentWorks\Exile\Models\Warning;
use EloquentWorks\Exile\Services\ExileManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** @mixin Model */
trait Bannable
{
    /**
     * Get all of the bans for the model.
     *
     * @return MorphMany<Ban, $this> Returns the relationship instance for the bans associated with the model.
     */
    public function bans(): MorphMany
    {
        /** @var class-string<Ban> $model */
        $model = config('exile.models.ban', Ban::class);

        /** @var MorphMany<Ban, $this> $relation */
        $relation = $this->morphMany($model, 'bannable');

        // Return the relationship instance for the bans associated with the model.
        return $relation;
    }

    /**
     * Get all of the restrictions for the model.
     *
     * @return MorphMany<Restriction, $this> Returns the relationship instance for the restrictions associated with the model.
     */
    public function restrictions(): MorphMany
    {
        /** @var class-string<Restriction> $model */
        $model = config('exile.models.restriction', Restriction::class);

        /** @var MorphMany<Restriction, $this> $relation */
        $relation = $this->morphMany($model, 'restrictable');

        // Return the relationship instance for the restrictions associated with the model.
        return $relation;
    }

    /**
     * Get all of the strikes for the model.
     *
     * @return MorphMany<Strike, $this> Returns the relationship instance for the strikes associated with the model.
     */
    public function strikes(): MorphMany
    {
        /** @var class-string<Strike> $model */
        $model = config('exile.models.strike', Strike::class);

        /** @var MorphMany<Strike, $this> $relation */
        $relation = $this->morphMany($model, 'strikeable');

        // Return the relationship instance for the strikes associated with the model.
        return $relation;
    }

    /**
     * Get all of the warnings for the model.
     *
     * @return MorphMany<Warning, $this> Returns the relationship instance for the warnings associated with the model.
     */
    public function warnings(): MorphMany
    {
        /** @var class-string<Warning> $model */
        $model = config('exile.models.warning', Warning::class);

        /** @var MorphMany<Warning, $this> $relation */
        $relation = $this->morphMany($model, 'warnable');

        // Return the relationship instance for the warnings associated with the model.
        return $relation;
    }

    /**
     * Get all of the device fingerprints for the model.
     *
     * @return MorphMany<DeviceFingerprint, $this> Returns the relationship instance for the device fingerprints associated with the model.
     */
    public function deviceFingerprints(): MorphMany
    {
        /** @var class-string<DeviceFingerprint> $model */
        $model = config('exile.models.device_fingerprint', DeviceFingerprint::class);

        /** @var MorphMany<DeviceFingerprint, $this> $relation */
        $relation = $this->morphMany($model, 'fingerprintable');

        // Return the relationship instance for the device fingerprints associated with the model.
        return $relation;
    }

    /**
     * Ban the model.
     *
     * @param  string|null  $reason  The reason for the ban.
     * @param  DateTimeInterface|null  $expiresAt  The expiration date of the ban.
     * @param  Model|null  $moderator  The moderator responsible for the ban.
     * @param  string|null  $category  The category of the ban.
     * @param  string|null  $internalNotes  Internal notes for the ban.
     * @param  array<string, mixed>  $metadata  Additional metadata for the ban.
     * @return Ban Returns the created ban instance.
     */
    public function ban(
        ?string $reason = null,
        ?DateTimeInterface $expiresAt = null,
        ?Model $moderator = null,
        ?string $category = null,
        ?string $internalNotes = null,
        array $metadata = [],
    ): Ban {
        /** @var Model $account */
        $account = $this;

        // Call the ExileManager service to ban the account with the provided details.
        return app(ExileManager::class)->banAccount(
            $account,
            $reason,
            $expiresAt,
            $moderator,
            $category,
            $internalNotes,
            $metadata,
        );
    }

    /**
     * Ban the model with an IP address.
     *
     * @param  string  $ipAddress  The IP address to ban.
     * @param  string|null  $reason  The reason for the ban.
     * @param  DateTimeInterface|null  $expiresAt  The expiration date of the ban.
     * @param  Model|null  $moderator  The moderator responsible for the ban.
     * @param  string|null  $category  The category of the ban.
     * @param  string|null  $internalNotes  Internal notes for the ban.
     * @param  array<string, mixed>  $metadata  Additional metadata for the ban.
     * @return Ban Returns the created ban instance.
     */
    public function banWithIp(
        string $ipAddress,
        ?string $reason = null,
        ?DateTimeInterface $expiresAt = null,
        ?Model $moderator = null,
        ?string $category = null,
        ?string $internalNotes = null,
        array $metadata = [],
    ): Ban {
        /** @var Model $account */
        $account = $this;

        // Call the ExileManager service to ban the account and IP address with the provided details.
        return app(ExileManager::class)->banAccountAndIp(
            $account,
            $ipAddress,
            $reason,
            $expiresAt,
            $moderator,
            $category,
            $internalNotes,
            $metadata,
        );
    }

    /**
     * Check if the model is banned.
     *
     * @param  string|null  $ipAddress  The IP address to check.
     * @param  string|null  $deviceFingerprint  The device fingerprint to check.
     * @return bool Returns true if the model is banned, false otherwise.
     */
    public function isBanned(?string $ipAddress = null, ?string $deviceFingerprint = null): bool
    {
        /** @var Model $account */
        $account = $this;

        // Call the ExileManager service to check if the account is banned, considering the optional IP address and device fingerprint.
        return app(ExileManager::class)->isBanned($account, $ipAddress, $deviceFingerprint);
    }

    /**
     * Restrict the model.
     *
     * @param  RestrictionType  $type  The type of restriction.
     * @param  string|null  $reason  The reason for the restriction.
     * @param  DateTimeInterface|null  $expiresAt  The expiration date of the restriction.
     * @param  Model|null  $moderator  The moderator responsible for the restriction.
     * @param  string|null  $internalNotes  Internal notes for the restriction.
     * @param  array<string, mixed>  $metadata  Additional metadata for the restriction.
     * @return Restriction Returns the created restriction instance.
     */
    public function restrict(
        RestrictionType $type,
        ?string $reason = null,
        ?DateTimeInterface $expiresAt = null,
        ?Model $moderator = null,
        ?string $internalNotes = null,
        array $metadata = [],
    ): Restriction {
        /** @var Model $account */
        $account = $this;

        // Call the ExileManager service to restrict the account with the provided details.
        return app(ExileManager::class)->restrict(
            $account,
            $type,
            $reason,
            $expiresAt,
            $moderator,
            $internalNotes,
            $metadata,
        );
    }

    /**
     * Check if the model is restricted for a specific type.
     *
     * @param  RestrictionType  $type  The type of restriction to check.
     * @return bool Returns true if the model is restricted for the specified type, false otherwise.
     */
    public function isRestricted(RestrictionType $type): bool
    {
        /** @var Model $account */
        $account = $this;

        // Call the ExileManager service to check if the account is restricted for the specified type.
        return app(ExileManager::class)->isRestricted($account, $type);
    }

    /**
     * Check if the model is shadow banned.
     *
     * @return bool Returns true if the model is shadow banned, false otherwise.
     */
    public function isShadowBanned(): bool
    {
        // Check if the model is restricted with the Shadow restriction type.
        return $this->isRestricted(RestrictionType::Shadow);
    }

    /**
     * Issue a strike to the model.
     *
     * @param  string  $reason  The reason for the strike.
     * @param  int|null  $points  The number of points for the strike.
     * @param  string|null  $category  The category of the strike.
     * @param  DateTimeInterface|null  $expiresAt  The expiration date of the strike.
     * @param  Model|null  $moderator  The moderator responsible for the strike.
     * @param  array<string, mixed>  $metadata  Additional metadata for the strike.
     * @return Strike Returns the created strike instance.
     */
    public function strike(
        string $reason,
        ?int $points = null,
        ?string $category = null,
        ?DateTimeInterface $expiresAt = null,
        ?Model $moderator = null,
        array $metadata = [],
    ): Strike {
        /** @var Model $account */
        $account = $this;

        // Call the ExileManager service to issue a strike for the account with the provided details.
        return app(ExileManager::class)->strike(
            $account,
            $reason,
            $points,
            $category,
            $expiresAt,
            $moderator,
            $metadata,
        );
    }

    /**
     * Issue a warning to the model.
     *
     * @param  string  $reason  The reason for the warning.
     * @param  WarningSeverity  $severity  The severity of the warning.
     * @param  string|null  $category  The category of the warning.
     * @param  string|null  $internalNotes  Internal notes for the warning.
     * @param  Model|null  $moderator  The moderator responsible for the warning.
     * @param  array<string, mixed>  $metadata  Additional metadata for the warning.
     * @return Warning Returns the created warning instance.
     */
    public function warn(
        string $reason,
        WarningSeverity $severity = WarningSeverity::Medium,
        ?string $category = null,
        ?string $internalNotes = null,
        ?Model $moderator = null,
        array $metadata = [],
    ): Warning {
        /** @var Model $account */
        $account = $this;

        // Call the ExileManager service to issue a warning for the account with the provided details.
        return app(ExileManager::class)->warn(
            $account,
            $reason,
            $severity,
            $category,
            $internalNotes,
            $moderator,
            $metadata,
        );
    }

    /**
     * Get the total active strike points for the model.
     *
     * @return int Returns the total number of active strike points.
     */
    public function activeStrikePoints(): int
    {
        /** @var Model $account */
        $account = $this;

        // Call the ExileManager service to retrieve the total active strike points for the account.
        return app(ExileManager::class)->activeStrikePoints($account);
    }

    /**
     * Register a device fingerprint for the model.
     *
     * @param  string  $fingerprint  The device fingerprint.
     * @param  string|null  $ipAddress  The IP address associated with the device.
     * @param  string|null  $label  The label for the device.
     * @param  array<string, mixed>  $metadata  Additional metadata for the device.
     * @return DeviceFingerprint Returns the created device fingerprint instance.
     */
    public function registerDeviceFingerprint(
        string $fingerprint,
        ?string $ipAddress = null,
        ?string $label = null,
        array $metadata = [],
    ): DeviceFingerprint {
        /** @var Model $account */
        $account = $this;

        // Call the ExileManager service to register the device fingerprint for the account with the provided details.
        return app(ExileManager::class)->registerDevice($account, $fingerprint, $ipAddress, $label, $metadata);
    }
}
