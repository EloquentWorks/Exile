<?php

namespace EloquentWorks\Exile\Services;

use DateTimeInterface;
use EloquentWorks\Exile\Enums\AppealStatus;
use EloquentWorks\Exile\Enums\BanType;
use EloquentWorks\Exile\Enums\RestrictionType;
use EloquentWorks\Exile\Enums\WarningSeverity;
use EloquentWorks\Exile\Events\AppealResolved;
use EloquentWorks\Exile\Events\AppealSubmitted;
use EloquentWorks\Exile\Events\BanExpired;
use EloquentWorks\Exile\Models\Ban;
use EloquentWorks\Exile\Models\BanAppeal;
use EloquentWorks\Exile\Models\DeviceFingerprint;
use EloquentWorks\Exile\Models\Evidence;
use EloquentWorks\Exile\Models\Restriction;
use EloquentWorks\Exile\Models\Strike;
use EloquentWorks\Exile\Models\Warning;
use EloquentWorks\Exile\Support\EnforcementContext;
use EloquentWorks\Exile\Support\IdentifierHasher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use LogicException;

/**
 * The ExileManager class provides a high-level interface for managing bans, restrictions, strikes, warnings,
 * appeals, and evidence within the Exile system. It encapsulates the logic for issuing and revoking enforcement
 * actions, as well as handling appeals and evidence management.
 */
final class ExileManager
{
    /**
     * Constructs a new instance of the ExileManager.
     *
     * @param  EnforcementWriter  $writer  The writer responsible for issuing and revoking enforcement actions.
     * @param  EscalationEngine  $escalation  The engine responsible for evaluating escalation rules.
     * @param  IdentifierHasher  $hasher  The hasher used for hashing identifiers like IP addresses and device fingerprints.
     * @param  IpMatcher  $ipMatcher  The matcher used for checking if an IP address falls within a CIDR range.
     * @param  AuditLogger  $audit  The logger responsible for logging audit events.
     * @param  NotificationDispatcher  $notifications  The dispatcher responsible for sending notifications related to enforcement actions.
     */
    public function __construct(
        private readonly EnforcementWriter $writer,
        private readonly EscalationEngine $escalation,
        private readonly IdentifierHasher $hasher,
        private readonly IpMatcher $ipMatcher,
        private readonly AuditLogger $audit,
        private readonly NotificationDispatcher $notifications,
    ) {}

    /**
     * Issues a ban on an account.
     *
     * @param  Model  $account  The account to be banned.
     * @param  string|null  $reason  The reason for the ban.
     * @param  DateTimeInterface|null  $expiresAt  The expiration date of the ban, if applicable.
     * @param  Model|null  $moderator  The moderator issuing the ban, if applicable.
     * @param  string|null  $category  The category of the ban, if applicable.
     * @param  string|null  $internalNotes  Internal notes related to the ban, if applicable.
     * @param  array<string, mixed>  $metadata  Additional metadata associated with the ban.
     * @return Ban The issued ban instance.
     */
    public function banAccount(
        Model $account,
        ?string $reason = null,
        ?DateTimeInterface $expiresAt = null,
        ?Model $moderator = null,
        ?string $category = null,
        ?string $internalNotes = null,
        array $metadata = [],
    ): Ban {
        // Delegate the ban issuance to the EnforcementWriter with the appropriate parameters.
        return $this->writer->issueBan(
            type: BanType::Account,
            account: $account,
            category: $category,
            reason: $reason,
            internalNotes: $internalNotes,
            expiresAt: $expiresAt,
            moderator: $moderator,
            metadata: $metadata,
        );
    }

    /**
     * Issues a ban on an IP address.
     *
     * @param  string  $ipAddress  The IP address to be banned.
     * @param  string|null  $reason  The reason for the ban.
     * @param  DateTimeInterface|null  $expiresAt  The expiration date of the ban, if applicable.
     * @param  Model|null  $moderator  The moderator issuing the ban, if applicable.
     * @param  string|null  $category  The category of the ban, if applicable.
     * @param  string|null  $internalNotes  Internal notes related to the ban, if applicable.
     * @param  array<string, mixed>  $metadata  Additional metadata associated with the ban.
     * @return Ban The issued ban instance.
     */
    public function banIp(
        string $ipAddress,
        ?string $reason = null,
        ?DateTimeInterface $expiresAt = null,
        ?Model $moderator = null,
        ?string $category = null,
        ?string $internalNotes = null,
        array $metadata = [],
    ): Ban {
        // Delegate the ban issuance to the EnforcementWriter with the appropriate parameters.
        return $this->writer->issueBan(
            type: BanType::Ip,
            ipAddress: $ipAddress,
            category: $category,
            reason: $reason,
            internalNotes: $internalNotes,
            expiresAt: $expiresAt,
            moderator: $moderator,
            metadata: $metadata,
        );
    }

    /**
     * Issues a ban on an account and IP address.
     *
     * @param  Model  $account  The account to be banned.
     * @param  string  $ipAddress  The IP address to be banned.
     * @param  string|null  $reason  The reason for the ban.
     * @param  DateTimeInterface|null  $expiresAt  The expiration date of the ban, if applicable.
     * @param  Model|null  $moderator  The moderator issuing the ban, if applicable.
     * @param  string|null  $category  The category of the ban, if applicable.
     * @param  string|null  $internalNotes  Internal notes related to the ban, if applicable.
     * @param  array<string, mixed>  $metadata  Additional metadata associated with the ban.
     * @return Ban The issued ban instance.
     */
    public function banAccountAndIp(
        Model $account,
        string $ipAddress,
        ?string $reason = null,
        ?DateTimeInterface $expiresAt = null,
        ?Model $moderator = null,
        ?string $category = null,
        ?string $internalNotes = null,
        array $metadata = [],
    ): Ban {
        // Delegate the ban issuance to the EnforcementWriter with the appropriate parameters.
        return $this->writer->issueBan(
            type: BanType::AccountAndIp,
            account: $account,
            ipAddress: $ipAddress,
            category: $category,
            reason: $reason,
            internalNotes: $internalNotes,
            expiresAt: $expiresAt,
            moderator: $moderator,
            metadata: $metadata,
        );
    }

    /**
     * Issues a ban on a network.
     *
     * @param  string  $cidr  The CIDR range to be banned.
     * @param  string|null  $reason  The reason for the ban.
     * @param  DateTimeInterface|null  $expiresAt  The expiration date of the ban, if applicable.
     * @param  Model|null  $moderator  The moderator issuing the ban, if applicable.
     * @param  string|null  $category  The category of the ban, if applicable.
     * @param  string|null  $internalNotes  Internal notes related to the ban, if applicable.
     * @param  array<string, mixed>  $metadata  Additional metadata associated with the ban.
     * @return Ban The issued ban instance.
     */
    public function banNetwork(
        string $cidr,
        ?string $reason = null,
        ?DateTimeInterface $expiresAt = null,
        ?Model $moderator = null,
        ?string $category = null,
        ?string $internalNotes = null,
        array $metadata = [],
    ): Ban {
        // Delegate the ban issuance to the EnforcementWriter with the appropriate parameters.
        return $this->writer->issueBan(
            type: BanType::Network,
            cidr: $cidr,
            category: $category,
            reason: $reason,
            internalNotes: $internalNotes,
            expiresAt: $expiresAt,
            moderator: $moderator,
            metadata: $metadata,
        );
    }

    /**
     * Issues a ban on a device.
     *
     * @param  string  $deviceFingerprint  The device fingerprint to be banned.
     * @param  string|null  $reason  The reason for the ban.
     * @param  DateTimeInterface|null  $expiresAt  The expiration date of the ban, if applicable.
     * @param  Model|null  $moderator  The moderator issuing the ban, if applicable.
     * @param  string|null  $category  The category of the ban, if applicable.
     * @param  string|null  $internalNotes  Internal notes related to the ban, if applicable.
     * @param  array<string, mixed>  $metadata  Additional metadata associated with the ban.
     * @return Ban The issued ban instance.
     */
    public function banDevice(
        string $deviceFingerprint,
        ?string $reason = null,
        ?DateTimeInterface $expiresAt = null,
        ?Model $moderator = null,
        ?string $category = null,
        ?string $internalNotes = null,
        array $metadata = [],
    ): Ban {
        // Delegate the ban issuance to the EnforcementWriter with the appropriate parameters.
        return $this->writer->issueBan(
            type: BanType::Device,
            deviceFingerprint: $deviceFingerprint,
            category: $category,
            reason: $reason,
            internalNotes: $internalNotes,
            expiresAt: $expiresAt,
            moderator: $moderator,
            metadata: $metadata,
        );
    }

    /**
     * Issues a ban on an account, device, and IP address.
     *
     * @param  Model  $account  The account to be banned.
     * @param  string  $ipAddress  The IP address to be banned.
     * @param  string  $deviceFingerprint  The device fingerprint to be banned.
     * @param  string|null  $reason  The reason for the ban.
     * @param  DateTimeInterface|null  $expiresAt  The expiration date of the ban, if applicable.
     * @param  Model|null  $moderator  The moderator issuing the ban, if applicable.
     * @param  string|null  $category  The category of the ban, if applicable.
     * @param  string|null  $internalNotes  Internal notes related to the ban, if applicable.
     * @param  array<string, mixed>  $metadata  Additional metadata associated with the ban.
     * @return Ban The issued ban instance.
     */
    public function banAccountDeviceAndIp(
        Model $account,
        string $ipAddress,
        string $deviceFingerprint,
        ?string $reason = null,
        ?DateTimeInterface $expiresAt = null,
        ?Model $moderator = null,
        ?string $category = null,
        ?string $internalNotes = null,
        array $metadata = [],
    ): Ban {
        // Delegate the ban issuance to the EnforcementWriter with the appropriate parameters.
        return $this->writer->issueBan(
            type: BanType::AccountDeviceAndIp,
            account: $account,
            ipAddress: $ipAddress,
            deviceFingerprint: $deviceFingerprint,
            category: $category,
            reason: $reason,
            internalNotes: $internalNotes,
            expiresAt: $expiresAt,
            moderator: $moderator,
            metadata: $metadata,
        );
    }

    /**
     * Resolves the active ban for a given enforcement context.
     *
     * @param  EnforcementContext  $context  The enforcement context containing account, IP address, and device fingerprint information.
     * @return Ban|null The active ban if found, or null if no active ban exists.
     */
    public function resolveActiveBan(EnforcementContext $context): ?Ban
    {
        /** @var class-string<Ban> $modelClass */
        $modelClass = config('exile.models.ban', Ban::class);

        // Check for an active ban associated with the account, if provided in the context.
        if ($context->account !== null) {
            $accountBan = $modelClass::query()
                ->active()
                ->where('bannable_type', $context->account->getMorphClass())
                ->where('bannable_id', $context->account->getKey())
                ->whereIn('type', [
                    BanType::Account->value,
                    BanType::AccountAndIp->value,
                    BanType::AccountDeviceAndIp->value,
                ])
                ->latest('id')
                ->first();

            // If an active ban is found for the account, return it.
            if ($accountBan instanceof Ban) {
                return $accountBan;
            }
        }

        // Check for an active ban associated with the IP address, if provided in the context.
        if ($context->ipAddress !== null) {
            $ipHash = $this->hasher->hashIp($context->ipAddress);

            // Query for an active ban associated with the hashed IP address.
            $ipBan = $modelClass::query()
                ->active()
                ->where('ip_hash', $ipHash)
                ->whereIn('type', [
                    BanType::Ip->value,
                    BanType::AccountAndIp->value,
                    BanType::AccountDeviceAndIp->value,
                ])
                ->latest('id')
                ->first();

            // If an active ban is found for the IP address, return it.
            if ($ipBan instanceof Ban) {
                return $ipBan;
            }

            // Check for an active network ban that matches the provided IP address using CIDR notation.
            $networkBan = $modelClass::query()
                ->active()
                ->where('type', BanType::Network->value)
                ->whereNotNull('cidr')
                ->get()
                ->first(fn (Ban $ban): bool => $ban->cidr !== null && $this->ipMatcher->contains($ban->cidr, $context->ipAddress));

            // If an active network ban is found that matches the IP address, return it.
            if ($networkBan instanceof Ban) {
                return $networkBan;
            }
        }

        // Check for an active ban associated with the device fingerprint, if provided in the context.
        if ($context->deviceFingerprint !== null) {
            // Hash the device fingerprint for secure storage and comparison.
            $deviceHash = $this->hasher->hashDevice($context->deviceFingerprint);

            // Query for an active ban associated with the hashed device fingerprint.
            $deviceBan = $modelClass::query()
                ->active()
                ->where('device_hash', $deviceHash)
                ->whereIn('type', [
                    BanType::Device->value,
                    BanType::AccountDeviceAndIp->value,
                ])
                ->latest('id')
                ->first();

            // If an active ban is found for the device fingerprint, return it.
            if ($deviceBan instanceof Ban) {
                return $deviceBan;
            }
        }

        // If no active ban is found for the provided context, return null.
        return null;
    }

    /**
     * Checks if an account is currently banned based on the provided enforcement context.
     *
     * @param  Model  $account  The account to check for an active ban.
     * @param  string|null  $ipAddress  The IP address to check for an active ban (optional).
     * @param  string|null  $deviceFingerprint  The device fingerprint to check for an active ban (optional).
     * @return bool True if the account is banned, false otherwise.
     */
    public function isBanned(Model $account, ?string $ipAddress = null, ?string $deviceFingerprint = null): bool
    {
        // Create an enforcement context with the provided account, IP address, and device fingerprint.
        return $this->resolveActiveBan(new EnforcementContext($account, $ipAddress, $deviceFingerprint)) !== null;
    }

    /**
     * Issues a restriction on an account.
     *
     * @param  Model  $account  The account to be restricted.
     * @param  RestrictionType  $type  The type of restriction to be applied.
     * @param  string|null  $reason  The reason for the restriction.
     * @param  DateTimeInterface|null  $expiresAt  The expiration date of the restriction, if applicable.
     * @param  Model|null  $moderator  The moderator issuing the restriction, if applicable.
     * @param  string|null  $internalNotes  Internal notes related to the restriction, if applicable.
     * @param  array<string, mixed>  $metadata  Additional metadata associated with the restriction.
     * @return Restriction The issued restriction instance.
     */
    public function restrict(
        Model $account,
        RestrictionType $type,
        ?string $reason = null,
        ?DateTimeInterface $expiresAt = null,
        ?Model $moderator = null,
        ?string $internalNotes = null,
        array $metadata = [],
    ): Restriction {
        // Delegate the restriction issuance to the EnforcementWriter with the appropriate parameters.
        return $this->writer->issueRestriction(
            account: $account,
            type: $type,
            reason: $reason,
            internalNotes: $internalNotes,
            expiresAt: $expiresAt,
            moderator: $moderator,
            metadata: $metadata,
        );
    }

    /**
     * Retrieves the active restriction for a given account and restriction type.
     *
     * @param  Model  $account  The account to check for an active restriction.
     * @param  RestrictionType  $type  The type of restriction to check for.
     * @return Restriction|null The active restriction if found, or null if no active restriction exists.
     */
    public function activeRestrictionFor(Model $account, RestrictionType $type): ?Restriction
    {
        /** @var class-string<Restriction> $modelClass */
        $modelClass = config('exile.models.restriction', Restriction::class);

        // Determine the types of restrictions to check for based on the provided restriction type.
        $types = match ($type) {
            RestrictionType::Posting => [RestrictionType::Posting->value, RestrictionType::ReadOnly->value],
            default => [$type->value],
        };

        // Query for the latest active restriction associated with the account and the specified restriction types.
        $restriction = $modelClass::query()
            ->active()
            ->where('restrictable_type', $account->getMorphClass())
            ->where('restrictable_id', $account->getKey())
            ->whereIn('type', $types)
            ->latest('id')
            ->first();

        // Return the active restriction if found, or null if no active restriction exists.
        return $restriction instanceof Restriction ? $restriction : null;
    }

    /**
     * Checks if an account is currently restricted based on the provided restriction type.
     *
     * @param  Model  $account  The account to check for an active restriction.
     * @param  RestrictionType  $type  The type of restriction to check for.
     * @return bool True if the account is restricted, false otherwise.
     */
    public function isRestricted(Model $account, RestrictionType $type): bool
    {
        // Check if there is an active restriction for the account and the specified restriction type.
        return $this->activeRestrictionFor($account, $type) !== null;
    }

    /**
     * Issues a strike against an account.
     *
     * @param  Model  $account  The account to receive the strike.
     * @param  string  $reason  The reason for the strike.
     * @param  int|null  $points  The number of points associated with the strike (optional).
     * @param  string|null  $category  The category of the strike (optional).
     * @param  DateTimeInterface|null  $expiresAt  The expiration date of the strike, if applicable (optional).
     * @param  Model|null  $moderator  The moderator issuing the strike, if applicable (optional).
     * @param  array<string, mixed>  $metadata  Additional metadata associated with the strike.
     * @return Strike The issued strike instance.
     */
    public function strike(
        Model $account,
        string $reason,
        ?int $points = null,
        ?string $category = null,
        ?DateTimeInterface $expiresAt = null,
        ?Model $moderator = null,
        array $metadata = [],
    ): Strike {
        // Delegate the strike issuance to the EnforcementWriter with the appropriate parameters.
        $strike = $this->writer->issueStrike(
            account: $account,
            reason: $reason,
            points: $points ?? (int) config('exile.strikes.default_points', 1),
            category: $category,
            expiresAt: $expiresAt,
            moderator: $moderator,
            metadata: $metadata,
        );

        // Evaluate the escalation rules for the account after issuing the strike.
        $this->escalation->evaluate($account);

        // Return the issued strike instance.
        return $strike;
    }

    /**
     * Issues a warning to an account.
     *
     * @param  Model  $account  The account to receive the warning.
     * @param  string  $reason  The reason for the warning.
     * @param  WarningSeverity  $severity  The severity level of the warning (default: Medium).
     * @param  string|null  $category  The category of the warning (optional).
     * @param  string|null  $internalNotes  Internal notes related to the warning (optional).
     * @param  Model|null  $moderator  The moderator issuing the warning, if applicable (optional).
     * @param  array<string, mixed>  $metadata  Additional metadata associated with the warning.
     * @return Warning The issued warning instance.
     */
    public function warn(
        Model $account,
        string $reason,
        WarningSeverity $severity = WarningSeverity::Medium,
        ?string $category = null,
        ?string $internalNotes = null,
        ?Model $moderator = null,
        array $metadata = [],
    ): Warning {
        // Delegate the warning issuance to the EnforcementWriter with the appropriate parameters.
        return $this->writer->issueWarning(
            account: $account,
            reason: $reason,
            severity: $severity,
            category: $category,
            internalNotes: $internalNotes,
            moderator: $moderator,
            metadata: $metadata,
        );
    }

    /**
     * Retrieves the total active strike points for a given account.
     *
     * @param  Model  $account  The account to check for active strike points.
     * @return int The total number of active strike points for the account.
     */
    public function activeStrikePoints(Model $account): int
    {
        /** @var class-string<Strike> $modelClass */
        $modelClass = config('exile.models.strike', Strike::class);

        // Query the database to sum the points of all active strikes associated with the account.
        return (int) $modelClass::query()
            ->active()
            ->where('strikeable_type', $account->getMorphClass())
            ->where('strikeable_id', $account->getKey())
            ->sum('points');
    }

    /**
     * Registers a device fingerprint for an account.
     *
     * @param  Model  $account  The account to associate with the device fingerprint.
     * @param  string  $fingerprint  The device fingerprint to register.
     * @param  string|null  $ipAddress  The IP address associated with the device (optional).
     * @param  string|null  $label  A label for the device (optional).
     * @param  array<string, mixed>  $metadata  Additional metadata associated with the device fingerprint.
     * @return DeviceFingerprint The registered device fingerprint instance.
     */
    public function registerDevice(
        Model $account,
        string $fingerprint,
        ?string $ipAddress = null,
        ?string $label = null,
        array $metadata = [],
    ): DeviceFingerprint {
        /** @var class-string<DeviceFingerprint> $modelClass */
        $modelClass = config('exile.models.device_fingerprint', DeviceFingerprint::class);
        $fingerprintHash = $this->hasher->hashDevice($fingerprint);

        /** @var DeviceFingerprint $device */
        $device = $modelClass::query()->firstOrNew([
            'fingerprintable_type' => $account->getMorphClass(),
            'fingerprintable_id' => $account->getKey(),
            'fingerprint_hash' => $fingerprintHash,
        ]);

        // If the device fingerprint is being registered for the first time, set the first_seen_at timestamp to the current time.
        if (! $device->exists) {
            $device->first_seen_at = now();
        }

        // Update the device fingerprint record with the provided information, including the hashed IP address, label, metadata, and last_seen_at timestamp.
        $device->forceFill([
            'last_ip_hash' => $ipAddress !== null ? $this->hasher->hashIp($ipAddress) : $device->last_ip_hash,
            'label' => $label ?? $device->label,
            'metadata' => $metadata !== [] ? $metadata : $device->metadata,
            'last_seen_at' => now(),
        ])->save();

        // Log the device registration event for auditing purposes, including the device fingerprint, account, and label.
        $this->audit->log('device.seen', $device, $account, ['label' => $label]);

        // Return the registered device fingerprint instance.
        return $device;
    }

    /**
     * Submits an appeal for a given ban.
     *
     * @param  Ban  $ban  The ban for which the appeal is being submitted.
     * @param  Model  $appellant  The account submitting the appeal.
     * @param  string  $message  The message associated with the appeal.
     * @return BanAppeal The submitted ban appeal instance.
     *
     * @throws LogicException If ban appeals are disabled or if a pending appeal already exists for the ban.
     * @throws InvalidArgumentException If the appeal message is empty or exceeds the maximum allowed length.
     */
    public function submitAppeal(Ban $ban, Model $appellant, string $message): BanAppeal
    {
        // Check if ban appeals are enabled in the configuration. If not, throw a LogicException.
        if (! config('exile.appeals.enabled', true)) {
            throw new LogicException('Ban appeals are disabled.');
        }

        // Validate the appeal message by trimming whitespace and checking its length against the configured maximum length. If the message is empty or exceeds the maximum length, throw an InvalidArgumentException.
        $message = trim($message);
        $maxLength = (int) config('exile.appeals.max_message_length', 3000);

        // Validate the appeal message by trimming whitespace and checking its length against the configured maximum length. If the message is empty or exceeds the maximum length, throw an InvalidArgumentException.
        if ($message === '' || mb_strlen($message) > $maxLength) {
            throw new InvalidArgumentException("Appeal messages must contain between 1 and {$maxLength} characters.");
        }

        // Check if multiple pending appeals are allowed for the ban. If not, check if a pending appeal already exists for the ban. If a pending appeal exists, throw a LogicException.
        if (! config('exile.appeals.allow_multiple_pending', false)
            && $ban->appeals()->where('status', AppealStatus::Pending->value)->exists()) {
            throw new LogicException('A pending appeal already exists for this ban.');
        }

        /** @var class-string<BanAppeal> $modelClass */
        $modelClass = config('exile.models.appeal', BanAppeal::class);

        /** @var BanAppeal $appeal */
        $appeal = $modelClass::query()->create([
            'ban_id' => $ban->getKey(),
            'appellant_type' => $appellant->getMorphClass(),
            'appellant_id' => $appellant->getKey(),
            'status' => AppealStatus::Pending,
            'message' => $message,
        ]);

        // Dispatch the AppealSubmitted event to notify listeners that a new appeal has been submitted.
        event(new AppealSubmitted($appeal));
        $this->audit->log('appeal.submitted', $appeal, $appellant, ['ban_id' => $ban->getKey()]);

        // Return the submitted ban appeal instance.
        return $appeal;
    }

    /**
     * Resolves a pending appeal for a given ban.
     *
     * @param  BanAppeal  $appeal  The appeal to be resolved.
     * @param  AppealStatus  $status  The status to set for the appeal (Approved or Denied).
     * @param  Model  $reviewer  The account reviewing the appeal.
     * @param  string|null  $response  An optional response message from the reviewer.
     * @return bool True if the appeal was successfully resolved, false otherwise.
     *
     * @throws LogicException If the appeal is not pending or if an invalid status is provided.
     */
    public function resolveAppeal(
        BanAppeal $appeal,
        AppealStatus $status,
        Model $reviewer,
        ?string $response = null,
    ): bool {
        // Check if the appeal is pending. If not, throw a LogicException.
        if (! $appeal->isPending()) {
            throw new LogicException('Only pending appeals may be resolved.');
        }

        // Check if the provided status is either Approved or Denied. If not, throw an InvalidArgumentException.
        if (! in_array($status, [AppealStatus::Approved, AppealStatus::Denied], true)) {
            throw new InvalidArgumentException('An appeal may only be approved or denied by a reviewer.');
        }

        // Update the appeal's status, response, reviewer information, and reviewed_at timestamp, and save the changes to the database.
        $saved = $appeal->forceFill([
            'status' => $status,
            'response' => $response,
            'reviewed_by_type' => $reviewer->getMorphClass(),
            'reviewed_by_id' => $reviewer->getKey(),
            'reviewed_at' => now(),
        ])->save();

        // If the appeal was not successfully saved, return false to indicate failure.
        if (! $saved) {
            return false;
        }

        // If the appeal was approved, revoke the associated ban using the EnforcementWriter.
        if ($status === AppealStatus::Approved) {
            $this->writer->revokeBan($appeal->ban, $reviewer);
        }

        // Dispatch the AppealResolved event to notify listeners that the appeal has been resolved, and log the appeal resolution in the audit log with relevant details.
        event(new AppealResolved($appeal));
        $this->audit->log('appeal.resolved', $appeal, $reviewer, ['status' => $status->value]);

        // Return true to indicate that the appeal was successfully resolved.
        return true;
    }

    /**
     * Withdraws a pending appeal for a given ban.
     *
     * @param  BanAppeal  $appeal  The appeal to be withdrawn.
     * @param  Model  $appellant  The account withdrawing the appeal.
     * @return bool True if the appeal was successfully withdrawn, false otherwise.
     *
     * @throws LogicException If the appeal is not pending.
     */
    public function withdrawAppeal(BanAppeal $appeal, Model $appellant): bool
    {
        // Check if the appeal is pending. If not, throw a LogicException.
        if (! $appeal->isPending()) {
            throw new LogicException('Only pending appeals may be withdrawn.');
        }

        // Update the appeal's status to Withdrawn and set the reviewed_at timestamp to the current time, then save the changes to the database.
        $saved = $appeal->forceFill([
            'status' => AppealStatus::Withdrawn,
            'reviewed_at' => now(),
        ])->save();

        // If the appeal was successfully saved, log the appeal withdrawal in the audit log with relevant details.
        if ($saved) {
            $this->audit->log('appeal.withdrawn', $appeal, $appellant);
        }

        // Return true if the appeal was successfully withdrawn, false otherwise.
        return $saved;
    }

    /**
     * Attaches an evidence file to a given subject model.
     *
     * @param  Model  $subject  The subject model to which the evidence will be attached.
     * @param  string  $disk  The disk where the evidence file is stored.
     * @param  string  $path  The path to the evidence file on the specified disk.
     * @param  string|null  $originalName  The original name of the evidence file (optional).
     * @param  string|null  $mimeType  The MIME type of the evidence file (optional).
     * @param  int|null  $sizeBytes  The size of the evidence file in bytes (optional).
     * @param  Model|null  $uploadedBy  The account that uploaded the evidence (optional).
     * @param  array<string, mixed>  $metadata  Additional metadata associated with the evidence (optional).
     * @return Evidence The created evidence instance.
     */
    public function attachEvidence(
        Model $subject,
        string $disk,
        string $path,
        ?string $originalName = null,
        ?string $mimeType = null,
        ?int $sizeBytes = null,
        ?Model $uploadedBy = null,
        array $metadata = [],
    ): Evidence {
        /** @var class-string<Evidence> $modelClass */
        $modelClass = config('exile.models.evidence', Evidence::class);

        /** @var Evidence $evidence */
        $evidence = $modelClass::query()->create([
            'evidenceable_type' => $subject->getMorphClass(),
            'evidenceable_id' => $subject->getKey(),
            'disk' => $disk,
            'path' => $path,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'size_bytes' => $sizeBytes,
            'metadata' => $metadata,
            'uploaded_by_type' => $uploadedBy?->getMorphClass(),
            'uploaded_by_id' => $uploadedBy?->getKey(),
        ]);

        // Log the evidence attachment event for auditing purposes, including the subject, uploader, and evidence ID.
        $this->audit->log('evidence.attached', $subject, $uploadedBy, ['evidence_id' => $evidence->getKey()]);

        // Return the created evidence instance.
        return $evidence;
    }

    /**
     * Stores an evidence file for a given subject model.
     *
     * @param  Model  $subject  The subject model to which the evidence will be attached.
     * @param  UploadedFile  $file  The uploaded file to be stored as evidence.
     * @param  Model|null  $uploadedBy  The account that uploaded the evidence (optional).
     * @param  array<string, mixed>  $metadata  Additional metadata associated with the evidence (optional).
     * @return Evidence The created evidence instance.
     *
     * @throws InvalidArgumentException If the evidence file exceeds the maximum allowed size.
     * @throws LogicException If the evidence file could not be stored.
     */
    public function storeEvidence(
        Model $subject,
        UploadedFile $file,
        ?Model $uploadedBy = null,
        array $metadata = [],
    ): Evidence {
        // Retrieve the maximum allowed size for evidence files from the configuration, defaulting to 10 MB (10240 KB) if not specified.
        $maxKilobytes = (int) config('exile.evidence.max_size_kilobytes', 10240);

        // Check if the uploaded file exceeds the maximum allowed size. If it does, throw an InvalidArgumentException.
        if ($file->getSize() > $maxKilobytes * 1024) {
            throw new InvalidArgumentException("Evidence files may not exceed {$maxKilobytes} KB.");
        }

        // Retrieve the disk and directory configuration for storing evidence files, defaulting to 'local' disk and 'exile/evidence' directory if not specified.
        $disk = (string) config('exile.evidence.disk', 'local');
        $directory = trim((string) config('exile.evidence.directory', 'exile/evidence'), '/');
        $storedPath = $file->store($directory, $disk);

        // If the file could not be stored, throw a LogicException.
        if ($storedPath === false) {
            throw new LogicException('The evidence file could not be stored.');
        }

        // Attach the stored evidence file to the subject model using the attachEvidence method, passing in the necessary parameters such as disk, path, original name, MIME type, size, uploader, and metadata.
        return $this->attachEvidence(
            subject: $subject,
            disk: $disk,
            path: $storedPath,
            originalName: $file->getClientOriginalName(),
            mimeType: $file->getMimeType(),
            sizeBytes: $file->getSize(),
            uploadedBy: $uploadedBy,
            metadata: $metadata,
        );
    }

    /**
     * Deletes an evidence record and optionally removes the associated file from storage.
     *
     * @param  Evidence  $evidence  The evidence record to be deleted.
     * @param  bool  $deleteFile  Whether to delete the associated file from storage (default: true).
     * @return bool True if the evidence record was successfully deleted, false otherwise.
     */
    public function deleteEvidence(Evidence $evidence, bool $deleteFile = true): bool
    {
        // If the $deleteFile parameter is true, delete the associated file from storage using the specified disk and path.
        if ($deleteFile) {
            Storage::disk($evidence->disk)->delete($evidence->path);
        }

        // Log the evidence deletion event for auditing purposes, including the subject and evidence ID.
        $this->audit->log('evidence.deleted', $evidence->evidenceable, null, ['evidence_id' => $evidence->getKey()]);

        // Delete the evidence record from the database and return true if the deletion was successful, false otherwise.
        return (bool) $evidence->delete();
    }

    /**
     * Revokes a ban.
     *
     * @param  Ban  $ban  The ban to be revoked.
     * @param  Model|null  $moderator  The moderator revoking the ban (optional).
     * @return bool True if the ban was successfully revoked, false otherwise.
     */
    public function revokeBan(Ban $ban, ?Model $moderator = null): bool
    {
        // Delegate the revocation of the ban to the EnforcementWriter, passing in the ban and the optional moderator.
        return $this->writer->revokeBan($ban, $moderator);
    }

    /**
     * Revokes a restriction.
     *
     * @param  Restriction  $restriction  The restriction to be revoked.
     * @param  Model|null  $moderator  The moderator revoking the restriction (optional).
     * @return bool True if the restriction was successfully revoked, false otherwise.
     */
    public function revokeRestriction(Restriction $restriction, ?Model $moderator = null): bool
    {
        // Delegate the revocation of the restriction to the EnforcementWriter, passing in the restriction and the optional moderator.
        return $this->writer->revokeRestriction($restriction, $moderator);
    }

    /**
     * Revokes a strike.
     *
     * @param  Strike  $strike  The strike to be revoked.
     * @param  Model|null  $moderator  The moderator revoking the strike (optional).
     * @return bool True if the strike was successfully revoked, false otherwise.
     */
    public function revokeStrike(Strike $strike, ?Model $moderator = null): bool
    {
        // Delegate the revocation of the strike to the EnforcementWriter, passing in the strike and the optional moderator.
        return $this->writer->revokeStrike($strike, $moderator);
    }

    /**
     * Marks a ban as expired and triggers the associated events and notifications.
     *
     * @param  Ban  $ban  The ban to be marked as expired.
     * @return bool True if the ban was successfully marked as expired, false otherwise.
     */
    public function markBanExpired(Ban $ban): bool
    {
        // Check if the ban is expired and has not been notified yet. If the ban is not expired or has already been notified, return false.
        if (! $ban->isExpired() || $ban->expired_notified_at !== null) {
            return false;
        }

        // Update the ban's expired_notified_at timestamp to the current time and save the changes to the database.
        $saved = $ban->forceFill(['expired_notified_at' => now()])->save();

        // If the ban was successfully marked as expired, dispatch the BanExpired event, log the expiration in the audit log, and send a notification about the ban expiration.
        if ($saved) {
            event(new BanExpired($ban));
            $this->audit->log('ban.expired', $ban);
            $this->notifications->banExpired($ban);
        }

        // Return true if the ban was successfully marked as expired, false otherwise.
        return $saved;
    }
}
