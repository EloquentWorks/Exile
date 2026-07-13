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
use InvalidArgumentException;

/**
 * Service responsible for writing enforcement actions to the database.
 */
final class EnforcementWriter
{
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
     * @param  Model|null  $account  The account to ban (optional).
     * @param  string|null  $ipAddress  The IP address to ban (optional).
     * @param  string|null  $cidr  The CIDR range for a network ban (optional).
     * @param  string|null  $deviceFingerprint  The device fingerprint to ban (optional).
     * @param  string|null  $category  The category of the ban (optional).
     * @param  string|null  $reason  The reason for the ban (optional).
     * @param  string|null  $internalNotes  Internal notes for the ban (optional).
     * @param  DateTimeInterface|null  $expiresAt  The expiration date of the ban (optional).
     * @param  Model|null  $moderator  The moderator issuing the ban (optional).
     * @param  array<string, mixed>  $metadata  Additional metadata for the ban (optional).
     * @return Ban The created ban instance.
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
        /** Validate the expiration date, category, and ban requirements. */
        $this->validateExpiration($expiresAt);
        $this->validateCategory($category);
        $this->validateBanRequirements($type, $account, $ipAddress, $cidr, $deviceFingerprint);

        /** @var class-string<Ban> $modelClass */
        $modelClass = config('exile.models.ban', Ban::class);

        // Prepare the attributes for creating the ban record.
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

        // If an IP address is provided, normalize and hash it for storage.
        if ($ipAddress !== null) {
            $normalizedIp = $this->hasher->normalizeIp($ipAddress);
            $attributes['ip_address'] = $normalizedIp;
            $attributes['ip_hash'] = $this->hasher->hashIp($normalizedIp);
        }

        // If a CIDR range is provided, normalize it for storage.
        if ($cidr !== null) {
            $attributes['cidr'] = $this->ipMatcher->normalizeCidr($cidr);
        }

        // If a device fingerprint is provided, hash it for storage.
        if ($deviceFingerprint !== null) {
            $attributes['device_hash'] = $this->hasher->hashDevice($deviceFingerprint);
        }

        /** @var Ban $ban */
        $ban = $modelClass::query()->create($attributes);

        // Trigger the BanIssued event, log the action in the audit log, and send notifications.
        event(new BanIssued($ban));
        $this->audit->log('ban.issued', $ban, $moderator, [
            'type' => $type->value,
            'category' => $category,
            'account_type' => $account?->getMorphClass(),
            'account_id' => $account?->getKey(),
        ]);
        $this->notifications->banIssued($ban);

        // Return the created ban instance.
        return $ban;
    }

    /**
     * Issue a restriction to an account.
     *
     * @param  Model  $account  The account to issue the restriction to.
     * @param  RestrictionType  $type  The type of restriction to issue.
     * @param  string|null  $reason  The reason for the restriction (optional).
     * @param  string|null  $internalNotes  Internal notes for the restriction (optional).
     * @param  DateTimeInterface|null  $expiresAt  The expiration date of the restriction (optional).
     * @param  Model|null  $moderator  The moderator issuing the restriction (optional).
     * @param  array<string, mixed>  $metadata  Additional metadata for the restriction (optional).
     * @return Restriction The created restriction instance.
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
        // Validate the expiration date for the restriction.
        $this->validateExpiration($expiresAt);

        /** @var class-string<Restriction> $modelClass */
        $modelClass = config('exile.models.restriction', Restriction::class);

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

        // Trigger the RestrictionIssued event and log the action in the audit log.
        event(new RestrictionIssued($restriction));
        $this->audit->log('restriction.issued', $restriction, $moderator, [
            'type' => $type->value,
            'account_type' => $account->getMorphClass(),
            'account_id' => $account->getKey(),
        ]);

        // Return the created restriction instance.
        return $restriction;
    }

    /**
     * Issue a strike to an account.
     *
     * @param  Model  $account  The account to issue the strike to.
     * @param  string  $reason  The reason for the strike.
     * @param  int  $points  The number of points for the strike (default is 1).
     * @param  string|null  $category  The category of the strike (optional).
     * @param  DateTimeInterface|null  $expiresAt  The expiration date of the strike (optional).
     * @param  Model|null  $moderator  The moderator issuing the strike (optional).
     * @param  array<string, mixed>  $metadata  Additional metadata for the strike (optional).
     * @return Strike The created strike instance.
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
        // Validate that the number of points for the strike is at least one.
        if ($points < 1) {
            throw new InvalidArgumentException('Strike points must be at least one.');
        }

        // Validate the expiration date and category for the strike.
        $this->validateExpiration($expiresAt);
        $this->validateCategory($category);

        /** @var class-string<Strike> $modelClass */
        $modelClass = config('exile.models.strike', Strike::class);

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

        // Trigger the StrikeIssued event and log the action in the audit log.
        event(new StrikeIssued($strike));
        $this->audit->log('strike.issued', $strike, $moderator, [
            'points' => $points,
            'account_type' => $account->getMorphClass(),
            'account_id' => $account->getKey(),
        ]);

        // Return the created strike instance.
        return $strike;
    }

    /**
     * Issue a warning to an account.
     *
     * @param  Model  $account  The account to issue the warning to.
     * @param  string  $reason  The reason for the warning.
     * @param  WarningSeverity  $severity  The severity level of the warning.
     * @param  string|null  $category  The category of the warning (optional).
     * @param  string|null  $internalNotes  Internal notes for the warning (optional).
     * @param  Model|null  $moderator  The moderator issuing the warning (optional).
     * @param  array<string, mixed>  $metadata  Additional metadata for the warning (optional).
     * @return Warning The created warning instance.
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
        // Validate the category for the warning.
        $this->validateCategory($category);

        /** @var class-string<Warning> $modelClass */
        $modelClass = config('exile.models.warning', Warning::class);

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

        // Trigger the WarningIssued event and log the action in the audit log.
        event(new WarningIssued($warning));
        $this->audit->log('warning.issued', $warning, $moderator, [
            'severity' => $severity->value,
            'account_type' => $account->getMorphClass(),
            'account_id' => $account->getKey(),
        ]);

        // Return the created warning instance.
        return $warning;
    }

    /**
     * Revoke a ban.
     *
     * @param  Ban  $ban  The ban to revoke.
     * @param  Model|null  $moderator  The moderator revoking the ban (optional).
     * @return bool True if the ban was successfully revoked, false otherwise.
     */
    public function revokeBan(Ban $ban, ?Model $moderator = null): bool
    {
        // Check if the ban is already revoked. If so, return true.
        if ($ban->isRevoked()) {
            return true;
        }

        // Update the ban record to mark it as revoked, including the revocation timestamp and the moderator who revoked it.
        $saved = $ban->forceFill([
            'revoked_at' => now(),
            'revoked_by_type' => $moderator?->getMorphClass(),
            'revoked_by_id' => $moderator?->getKey(),
        ])->save();

        // If the ban was successfully revoked, trigger the BanRevoked event, log the action in the audit log, and send notifications.
        if ($saved) {
            event(new BanRevoked($ban));
            $this->audit->log('ban.revoked', $ban, $moderator);
            $this->notifications->banRevoked($ban);
        }

        // Return whether the ban was successfully revoked.
        return $saved;
    }

    /**
     * Revoke a restriction.
     *
     * @param  Restriction  $restriction  The restriction to revoke.
     * @param  Model|null  $moderator  The moderator revoking the restriction (optional).
     * @return bool True if the restriction was successfully revoked, false otherwise.
     */
    public function revokeRestriction(Restriction $restriction, ?Model $moderator = null): bool
    {
        // Check if the restriction is already revoked. If so, return true.
        if ($restriction->revoked_at !== null) {
            return true;
        }

        // Update the restriction record to mark it as revoked, including the revocation timestamp and the moderator who revoked it.
        $saved = $restriction->forceFill([
            'revoked_at' => now(),
            'revoked_by_type' => $moderator?->getMorphClass(),
            'revoked_by_id' => $moderator?->getKey(),
        ])->save();

        // If the restriction was successfully revoked, trigger the RestrictionRevoked event and log the action in the audit log.
        if ($saved) {
            event(new RestrictionRevoked($restriction));
            $this->audit->log('restriction.revoked', $restriction, $moderator);
        }

        // Return whether the restriction was successfully revoked.
        return $saved;
    }

    /**
     * Revoke a strike.
     *
     * @param  Strike  $strike  The strike to revoke.
     * @param  Model|null  $moderator  The moderator revoking the strike (optional).
     * @return bool True if the strike was successfully revoked, false otherwise.
     */
    public function revokeStrike(Strike $strike, ?Model $moderator = null): bool
    {
        // Check if the strike is already revoked. If so, return true.
        if ($strike->revoked_at !== null) {
            return true;
        }

        // Update the strike record to mark it as revoked, including the revocation timestamp and the moderator who revoked it.
        $saved = $strike->forceFill([
            'revoked_at' => now(),
            'revoked_by_type' => $moderator?->getMorphClass(),
            'revoked_by_id' => $moderator?->getKey(),
        ])->save();

        // If the strike was successfully revoked, log the action in the audit log.
        if ($saved) {
            $this->audit->log('strike.revoked', $strike, $moderator);
        }

        // Return whether the strike was successfully revoked.
        return $saved;
    }

    /**
     * Validate the expiration date of a warning.
     *
     * @param  ?DateTimeInterface  $expiresAt  The expiration date of the warning (optional).
     * @return void Returns nothing. Throws an exception if the expiration date is invalid.
     */
    private function validateExpiration(?DateTimeInterface $expiresAt): void
    {
        // Validate that the expiration date is in the future if provided.
        if ($expiresAt !== null && $expiresAt <= now()) {
            throw new InvalidArgumentException('The expiration date must be in the future.');
        }
    }

    /**
     * Validate the provided category against the configured categories.
     *
     * @param  string|null  $category  The category to validate.
     *
     * @throws InvalidArgumentException If the category is not configured.
     */
    private function validateCategory(?string $category): void
    {
        // If no category is provided, no validation is needed.
        if ($category === null) {
            return;
        }

        /** @var list<string> $categories */
        $categories = config('exile.categories', []);

        // If the configured categories are not empty and the provided category is not in the list, throw an exception.
        if ($categories !== [] && ! in_array($category, $categories, true)) {
            throw new InvalidArgumentException('The supplied enforcement category is not configured.');
        }
    }

    /**
     * Validate the requirements for issuing a ban based on its type.
     *
     * @param  BanType  $type  The type of the ban.
     * @param  Model|null  $account  The account to be banned (if applicable).
     * @param  string|null  $ipAddress  The IP address to be banned (if applicable).
     * @param  string|null  $cidr  The CIDR range for a network ban (if applicable).
     * @param  string|null  $deviceFingerprint  The device fingerprint to be banned (if applicable).
     *
     * @throws InvalidArgumentException If any required parameter is missing for the specified ban type.
     */
    private function validateBanRequirements(
        BanType $type,
        ?Model $account,
        ?string $ipAddress,
        ?string $cidr,
        ?string $deviceFingerprint,
    ): void {
        // Validate that the required parameters are provided based on the ban type.
        if (in_array($type, [BanType::Account, BanType::AccountAndIp, BanType::AccountDeviceAndIp], true) && $account === null) {
            throw new InvalidArgumentException('This ban type requires an account.');
        }

        // Validate that the required parameters are provided based on the ban type.
        if (in_array($type, [BanType::Ip, BanType::AccountAndIp, BanType::AccountDeviceAndIp], true) && $ipAddress === null) {
            throw new InvalidArgumentException('This ban type requires an IP address.');
        }

        // Validate that the required parameters are provided based on the ban type.
        if ($type === BanType::Network && $cidr === null) {
            throw new InvalidArgumentException('A network ban requires a CIDR range.');
        }

        // Validate that the required parameters are provided based on the ban type.
        if (in_array($type, [BanType::Device, BanType::AccountDeviceAndIp], true) && $deviceFingerprint === null) {
            throw new InvalidArgumentException('This ban type requires a device fingerprint.');
        }
    }
}
