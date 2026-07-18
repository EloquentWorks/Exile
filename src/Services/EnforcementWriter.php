<?php

namespace EloquentWorks\Exile\Services;

use DateTimeInterface;
use EloquentWorks\Exile\Enums\BanType;
use EloquentWorks\Exile\Enums\RestrictionType;
use EloquentWorks\Exile\Enums\WarningSeverity;
use EloquentWorks\Exile\Events\BanIssued;
use EloquentWorks\Exile\Events\BanRevoked;
use EloquentWorks\Exile\Events\RestrictionIssued;
use EloquentWorks\Exile\Events\RestrictionRevoked;
use EloquentWorks\Exile\Events\StrikeIssued;
use EloquentWorks\Exile\Events\WarningIssued;
use EloquentWorks\Exile\Models\Ban;
use EloquentWorks\Exile\Models\Restriction;
use EloquentWorks\Exile\Models\Strike;
use EloquentWorks\Exile\Models\Warning;
use EloquentWorks\Exile\Support\IdentifierHasher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Service responsible for writing enforcement actions to the database.
 */
final class EnforcementWriter
{
    /**
     * Constructs a new EnforcementWriter instance.
     *
     * @param  IdentifierHasher  $hasher  The identifier hasher for hashing sensitive data.
     * @param  IpMatcher  $ipMatcher  The IP matcher for validating and normalizing IP addresses.
     * @param  AuditLogger  $audit  The audit logger for logging enforcement actions.
     * @param  NotificationDispatcher  $notifications  The notification dispatcher for sending notifications.
     */
    public function __construct(
        private readonly IdentifierHasher $hasher,
        private readonly IpMatcher $ipMatcher,
        private readonly AuditLogger $audit,
        private readonly NotificationDispatcher $notifications,
    ) {}

    /**
     * Issue a ban to an account, IP address, or device.
     *
     * @param  BanType  $type  The type of ban to issue.
     * @param  Model|null  $account  The account to ban (if applicable).
     * @param  string|null  $ipAddress  The IP address to ban (if applicable).
     * @param  string|null  $cidr  The CIDR range to ban (if applicable).
     * @param  string|null  $deviceFingerprint  The device fingerprint to ban (if applicable).
     * @param  string|null  $category  The category of the ban (if applicable).
     * @param  string|null  $reason  The reason for the ban (if applicable).
     * @param  string|null  $internalNotes  Internal notes for the ban (if applicable).
     * @param  DateTimeInterface|null  $expiresAt  The expiration date of the ban (if applicable).
     * @param  Model|null  $moderator  The moderator issuing the ban (if applicable).
     * @param  array<string, mixed>  $metadata  Additional metadata for the ban.
     * @return Ban The issued ban instance.
     */
    public function issueBan(
        BanType $type,
        ?Model $account = null,
        ?string $ipAddress = null,
        ?string $cidr = null,
        ?string $deviceFingerprint = null,
        ?string $category = null,
        ?string $reason = null,
        ?string $internalNotes = null,
        ?DateTimeInterface $expiresAt = null,
        ?Model $moderator = null,
        array $metadata = [],
    ): Ban {
        // Validate the expiration date, category, and ban requirements before proceeding
        $this->validateExpiration($expiresAt);
        $this->validateCategory($category);
        $this->validateBanRequirements(
            $type,
            $account,
            $ipAddress,
            $cidr,
            $deviceFingerprint,
        );

        /** @var class-string<Ban> $modelClass */
        $modelClass = config('exile.models.ban', Ban::class);

        // Prepare the attributes for creating the ban record
        $attributes = [
            'type' => $type,
            'bannable_type' => $account?->getMorphClass(),
            'bannable_id' => $account?->getKey(),
            'category' => $category,
            'reason' => $reason,
            'internal_notes' => $internalNotes,
            'metadata' => $metadata,
            'banned_by_type' => $moderator?->getMorphClass(),
            'banned_by_id' => $moderator?->getKey(),
            'expires_at' => $expiresAt,
        ];

        // If an IP address is provided, normalize and hash it for storage
        if ($ipAddress !== null) {
            $normalizedIp = $this->hasher->normalizeIp($ipAddress);
            $attributes['ip_address'] = $normalizedIp;
            $attributes['ip_hash'] = $this->hasher->hashIp($normalizedIp);
        }

        // If a CIDR range is provided, normalize it for storage
        if ($cidr !== null) {
            $attributes['cidr'] = $this->ipMatcher->normalizeCidr($cidr);
        }

        // If a device fingerprint is provided, hash it for storage
        if ($deviceFingerprint !== null) {
            $attributes['device_hash'] = $this->hasher->hashDevice(
                $deviceFingerprint
            );
        }

        // Use a database transaction to create the ban record and log the action
        return DB::transaction(function () use (
            $modelClass,
            $attributes,
            $type,
            $category,
            $account,
            $moderator,
        ): Ban {
            /** @var Ban $ban */
            $ban = $modelClass::query()->create($attributes);

            // Log the ban issuance in the audit log with relevant details
            $this->audit->log(
                'ban.issued',
                $ban,
                $moderator,
                [
                    'type' => $type->value,
                    'category' => $category,
                    'account_type' => $account?->getMorphClass(),
                    'account_id' => $account?->getKey(),
                ],
            );

            // After the transaction is committed, trigger the BanIssued event and send notifications
            DB::afterCommit(function () use ($ban): void {
                event(new BanIssued($ban));
                $this->notifications->banIssued($ban);
            });

            // Return the created ban instance
            return $ban;
        });
    }

    /**
     * Issue a restriction to an account.
     *
     * @param  Model  $account  The account being restricted.
     * @param  RestrictionType  $type  The type of restriction being issued.
     * @param  string|null  $reason  The reason for the restriction (optional).
     * @param  string|null  $internalNotes  Internal notes for the restriction (optional).
     * @param  DateTimeInterface|null  $expiresAt  The expiration date of the restriction (optional).
     * @param  Model|null  $moderator  The moderator issuing the restriction (optional).
     * @param  array<string, mixed>  $metadata  Additional metadata for the restriction (optional).
     * @return Restriction The issued restriction instance.
     */
    public function issueRestriction(
        Model $account,
        RestrictionType $type,
        ?string $reason = null,
        ?string $internalNotes = null,
        ?DateTimeInterface $expiresAt = null,
        ?Model $moderator = null,
        array $metadata = [],
    ): Restriction {
        // Validate the expiration date before proceeding
        $this->validateExpiration($expiresAt);

        /** @var class-string<Restriction> $modelClass */
        $modelClass = config(
            'exile.models.restriction',
            Restriction::class
        );

        // Use a database transaction to create the restriction record and log the action
        return DB::transaction(function () use (
            $modelClass,
            $account,
            $type,
            $reason,
            $internalNotes,
            $expiresAt,
            $moderator,
            $metadata,
        ): Restriction {
            /** @var Restriction $restriction */
            $restriction = $modelClass::query()->create([
                'restrictable_type' => $account->getMorphClass(),
                'restrictable_id' => $account->getKey(),
                'type' => $type,
                'reason' => $reason,
                'internal_notes' => $internalNotes,
                'metadata' => $metadata,
                'issued_by_type' => $moderator?->getMorphClass(),
                'issued_by_id' => $moderator?->getKey(),
                'expires_at' => $expiresAt,
            ]);

            // Log the restriction issuance in the audit log with relevant details
            $this->audit->log(
                'restriction.issued',
                $restriction,
                $moderator,
                [
                    'type' => $type->value,
                    'account_type' => $account->getMorphClass(),
                    'account_id' => $account->getKey(),
                ],
            );

            // After the transaction is committed, trigger the RestrictionIssued event
            DB::afterCommit(function () use ($restriction): void {
                event(new RestrictionIssued($restriction));
            });

            // Return the created restriction instance
            return $restriction;
        });
    }

    /**
     * Issue a strike to an account.
     *
     * @param  Model  $account  The account receiving the strike.
     * @param  string  $reason  The reason for the strike.
     * @param  int  $points  The number of points for the strike.
     * @param  string|null  $category  The category of the strike (optional).
     * @param  DateTimeInterface|null  $expiresAt  The expiration date of the strike (optional).
     * @param  Model|null  $moderator  The moderator issuing the strike (optional).
     * @param  array<string, mixed>  $metadata  Additional metadata for the strike (optional).
     * @return Strike The issued strike instance.
     */
    public function issueStrike(
        Model $account,
        string $reason,
        int $points = 1,
        ?string $category = null,
        ?DateTimeInterface $expiresAt = null,
        ?Model $moderator = null,
        array $metadata = [],
    ): Strike {
        if ($points < 1) {
            throw new InvalidArgumentException(
                'Strike points must be at least one.'
            );
        }

        $expiresAt = $this->resolveStrikeExpiration($expiresAt);

        $this->validateExpiration($expiresAt);
        $this->validateCategory($category);

        /** @var class-string<Strike> $modelClass */
        $modelClass = config('exile.models.strike', Strike::class);

        return DB::transaction(function () use (
            $modelClass,
            $account,
            $reason,
            $points,
            $category,
            $expiresAt,
            $moderator,
            $metadata,
        ): Strike {
            /** @var Strike $strike */
            $strike = $modelClass::query()->create([
                'strikeable_type' => $account->getMorphClass(),
                'strikeable_id' => $account->getKey(),
                'points' => $points,
                'category' => $category,
                'reason' => $reason,
                'metadata' => $metadata,
                'issued_by_type' => $moderator?->getMorphClass(),
                'issued_by_id' => $moderator?->getKey(),
                'expires_at' => $expiresAt,
            ]);

            $this->audit->log(
                'strike.issued',
                $strike,
                $moderator,
                [
                    'points' => $points,
                    'account_type' => $account->getMorphClass(),
                    'account_id' => $account->getKey(),
                ],
            );

            DB::afterCommit(function () use ($strike): void {
                event(new StrikeIssued($strike));
            });

            return $strike;
        });
    }

    /**
     * Issue a warning to an account.
     *
     * @param  Model  $account  The account receiving the warning.
     * @param  string  $reason  The reason for the warning.
     * @param  WarningSeverity  $severity  The severity of the warning.
     * @param  string|null  $category  The category of the warning (optional).
     * @param  string|null  $internalNotes  Internal notes for the warning (optional).
     * @param  Model|null  $moderator  The moderator issuing the warning (optional).
     * @param  array<string, mixed>  $metadata  Additional metadata for the warning (optional).
     * @return Warning The issued warning instance.
     */
    public function issueWarning(
        Model $account,
        string $reason,
        WarningSeverity $severity = WarningSeverity::Medium,
        ?string $category = null,
        ?string $internalNotes = null,
        ?Model $moderator = null,
        array $metadata = [],
    ): Warning {
        $this->validateCategory($category);

        /** @var class-string<Warning> $modelClass */
        $modelClass = config('exile.models.warning', Warning::class);

        return DB::transaction(function () use (
            $modelClass,
            $account,
            $reason,
            $severity,
            $category,
            $internalNotes,
            $moderator,
            $metadata,
        ): Warning {
            /** @var Warning $warning */
            $warning = $modelClass::query()->create([
                'warnable_type' => $account->getMorphClass(),
                'warnable_id' => $account->getKey(),
                'severity' => $severity,
                'category' => $category,
                'reason' => $reason,
                'internal_notes' => $internalNotes,
                'metadata' => $metadata,
                'issued_by_type' => $moderator?->getMorphClass(),
                'issued_by_id' => $moderator?->getKey(),
            ]);

            $this->audit->log(
                'warning.issued',
                $warning,
                $moderator,
                [
                    'severity' => $severity->value,
                    'account_type' => $account->getMorphClass(),
                    'account_id' => $account->getKey(),
                ],
            );

            DB::afterCommit(function () use ($warning): void {
                event(new WarningIssued($warning));
            });

            return $warning;
        });
    }

    /**
     * Revoke a ban.
     *
     * @param  Ban  $ban  The ban to revoke.
     * @param  Model|null  $moderator  The moderator revoking the ban (optional).
     * @return bool True if the ban was successfully revoked, false otherwise.
     */
    public function revokeBan(
        Ban $ban,
        ?Model $moderator = null
    ): bool {
        if ($ban->isRevoked()) {
            return true;
        }

        return DB::transaction(function () use (
            $ban,
            $moderator,
        ): bool {
            $saved = $ban->forceFill([
                'revoked_at' => now(),
                'revoked_by_type' => $moderator?->getMorphClass(),
                'revoked_by_id' => $moderator?->getKey(),
            ])->save();

            if (! $saved) {
                return false;
            }

            $this->audit->log(
                'ban.revoked',
                $ban,
                $moderator
            );

            DB::afterCommit(function () use ($ban): void {
                event(new BanRevoked($ban));
                $this->notifications->banRevoked($ban);
            });

            return true;
        });
    }

    /**
     * Revoke a restriction.
     *
     * @param  Restriction  $restriction  The restriction to revoke.
     * @param  Model|null  $moderator  The moderator revoking the restriction (optional).
     * @return bool True if the restriction was successfully revoked, false otherwise.
     */
    public function revokeRestriction(
        Restriction $restriction,
        ?Model $moderator = null
    ): bool {
        // If the restriction is already revoked, return true without making any changes
        if ($restriction->revoked_at !== null) {
            return true;
        }

        // Use a database transaction to revoke the restriction and log the action
        return DB::transaction(function () use (
            $restriction,
            $moderator,
        ): bool {
            // Force-fill the revoked_at, revoked_by_type, and revoked_by_id fields and save the restriction
            $saved = $restriction->forceFill([
                'revoked_at' => now(),
                'revoked_by_type' => $moderator?->getMorphClass(),
                'revoked_by_id' => $moderator?->getKey(),
            ])->save();

            // If the save operation failed, return false to indicate that the restriction could not be revoked
            if (! $saved) {
                return false;
            }

            // Log the restriction revocation in the audit log with relevant details
            $this->audit->log(
                'restriction.revoked',
                $restriction,
                $moderator
            );

            // After the transaction is committed, trigger the RestrictionRevoked event
            DB::afterCommit(function () use (
                $restriction,
            ): void {
                // Trigger the RestrictionRevoked event to notify listeners that the restriction has been revoked
                event(
                    new RestrictionRevoked(
                        $restriction
                    )
                );
            });

            // Return true to indicate that the restriction was successfully revoked
            return true;
        });
    }

    /**
     * Revoke a strike.
     *
     * @param  Strike  $strike  The strike to revoke.
     * @param  Model|null  $moderator  The moderator revoking the strike (optional).
     * @return bool True if the strike was successfully revoked, false otherwise.
     */
    public function revokeStrike(
        Strike $strike,
        ?Model $moderator = null
    ): bool {
        // If the strike is already revoked, return true without making any changes
        if ($strike->revoked_at !== null) {
            return true;
        }

        // Use a database transaction to revoke the strike and log the action
        return DB::transaction(function () use (
            $strike,
            $moderator,
        ): bool {
            // Force-fill the revoked_at, revoked_by_type, and revoked_by_id fields and save the strike
            $saved = $strike->forceFill([
                'revoked_at' => now(),
                'revoked_by_type' => $moderator?->getMorphClass(),
                'revoked_by_id' => $moderator?->getKey(),
            ])->save();

            // If the save operation failed, return false to indicate that the strike could not be revoked
            if (! $saved) {
                return false;
            }

            // Log the strike revocation in the audit log with relevant details
            $this->audit->log(
                'strike.revoked',
                $strike,
                $moderator
            );

            // After the transaction is committed, trigger the StrikeRevoked event
            return true;
        });
    }

    /**
     * Validate an optional expiration date.
     *
     * @param  DateTimeInterface|null  $expiresAt  The expiration date to validate.
     *
     * @throws InvalidArgumentException If the expiration date is in the past.
     */
    private function validateExpiration(
        ?DateTimeInterface $expiresAt
    ): void {
        // If an expiration date is provided and it is in the past, throw an exception
        if (
            $expiresAt !== null
            && $expiresAt <= now()
        ) {
            throw new InvalidArgumentException(
                'The expiration date must be in the future.'
            );
        }
    }

    /**
     * Resolve the strike expiration date.
     *
     * @param  DateTimeInterface|null  $expiresAt  The expiration date to resolve.
     * @return DateTimeInterface|null The resolved expiration date, or null if not applicable.
     */
    private function resolveStrikeExpiration(
        ?DateTimeInterface $expiresAt
    ): ?DateTimeInterface {
        // If an expiration date is provided, return it as is
        if ($expiresAt !== null) {
            return $expiresAt;
        }

        // If no expiration date is provided, check the configuration for the default strike expiration
        $expireAfterDays = config(
            'exile.strikes.expire_after_days'
        );

        // If the configuration value is not a valid positive integer, return null to indicate no expiration
        if (
            ! is_numeric($expireAfterDays)
            || (int) $expireAfterDays < 1
        ) {
            return null;
        }

        // If a valid expiration period is configured, calculate and return the expiration date by adding the specified number of days to the current date and time
        return now()->addDays(
            (int) $expireAfterDays
        );
    }

    /**
     * Validate a configured enforcement category.
     *
     * @param  string|null  $category  The category to validate.
     *
     * @throws InvalidArgumentException If the category is not configured.
     */
    private function validateCategory(
        ?string $category
    ): void {
        // If no category is provided, no validation is needed
        if ($category === null) {
            return;
        }

        /** @var list<string> $categories */
        $categories = config(
            'exile.categories',
            []
        );

        // If categories are configured and the provided category is not in the list, throw an exception
        if (
            $categories !== []
            && ! in_array(
                $category,
                $categories,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'The supplied enforcement category is not configured.'
            );
        }
    }

    /**
     * Validate the identifiers required by a ban type.
     *
     * @param  BanType  $type  The type of ban.
     * @param  Model|null  $account  The account associated with the ban (optional).
     * @param  string|null  $ipAddress  The IP address associated with the ban (optional).
     * @param  string|null  $cidr  The CIDR range associated with the ban (optional).
     * @param  string|null  $deviceFingerprint  The device fingerprint associated with the ban (optional).
     *
     * @throws InvalidArgumentException If any required identifier is missing.
     */
    private function validateBanRequirements(
        BanType $type,
        ?Model $account,
        ?string $ipAddress,
        ?string $cidr,
        ?string $deviceFingerprint,
    ): void {
        if (
            in_array(
                $type,
                [
                    BanType::Account,
                    BanType::AccountAndIp,
                    BanType::AccountDeviceAndIp,
                ],
                true
            )
            && $account === null
        ) {
            throw new InvalidArgumentException(
                'This ban type requires an account.'
            );
        }

        if (
            in_array(
                $type,
                [
                    BanType::Ip,
                    BanType::AccountAndIp,
                    BanType::AccountDeviceAndIp,
                ],
                true
            )
            && $ipAddress === null
        ) {
            throw new InvalidArgumentException(
                'This ban type requires an IP address.'
            );
        }

        if (
            $type === BanType::Network
            && $cidr === null
        ) {
            throw new InvalidArgumentException(
                'A network ban requires a CIDR range.'
            );
        }

        if (
            in_array(
                $type,
                [
                    BanType::Device,
                    BanType::AccountDeviceAndIp,
                ],
                true
            )
            && $deviceFingerprint === null
        ) {
            throw new InvalidArgumentException(
                'This ban type requires a device fingerprint.'
            );
        }
    }
}
