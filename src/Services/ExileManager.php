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

final class ExileManager
{
    /**
     * Create a new instance of the ExileManager service.
     *
     * @param  EnforcementWriter  $writer  The service responsible for writing enforcement actions (bans, restrictions, strikes, warnings).
     * @param  EscalationEngine  $escalation  The service responsible for evaluating escalation rules based on strikes and other factors.
     * @param  IdentifierHasher  $hasher  The service responsible for hashing identifiers (IP addresses, device fingerprints) for privacy and security.
     * @param  IpMatcher  $ipMatcher  The service responsible for matching IP addresses against CIDR ranges for network bans.
     * @param  AuditLogger  $audit  The service responsible for logging audit events related to enforcement actions.
     * @param  NotificationDispatcher  $notifications  The service responsible for dispatching notifications related to enforcement actions.
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
     * Issue a ban to a specified account.
     *
     * @param  Model  $account  The model representing the account to be banned.
     * @param  string|null  $reason  An optional reason for issuing the ban.
     * @param  DateTimeInterface|null  $expiresAt  An optional expiration date for the ban.
     * @param  Model|null  $moderator  An optional model representing the moderator issuing the ban.
     * @param  string|null  $category  An optional category for the ban.
     * @param  string|null  $internalNotes  Optional internal notes related to the ban.
     * @param  array<string, mixed>  $metadata  Additional metadata to associate with the ban (optional).
     * @return Ban Returns the created Ban model instance.
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
        // Use the EnforcementWriter service to issue a ban to the specified account with the provided details.
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
     * Issue a ban to a specified IP address.
     *
     * @param  string  $ipAddress  The IP address to be banned.
     * @param  string|null  $reason  An optional reason for issuing the ban.
     * @param  DateTimeInterface|null  $expiresAt  An optional expiration date for the ban.
     * @param  Model|null  $moderator  An optional model representing the moderator issuing the ban.
     * @param  string|null  $category  An optional category for the ban.
     * @param  string|null  $internalNotes  Optional internal notes related to the ban.
     * @param  array<string, mixed>  $metadata  Additional metadata to associate with the ban (optional).
     * @return Ban Returns the created Ban model instance.
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
        // Use the EnforcementWriter service to issue a ban to the specified IP address with the provided details.
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
     * Issue a ban to a specified account and IP address.
     *
     * @param  Model  $account  The model representing the account to be banned.
     * @param  string  $ipAddress  The IP address to be banned.
     * @param  string|null  $reason  An optional reason for issuing the ban.
     * @param  DateTimeInterface|null  $expiresAt  An optional expiration date for the ban.
     * @param  Model|null  $moderator  An optional model representing the moderator issuing the ban.
     * @param  string|null  $category  An optional category for the ban.
     * @param  string|null  $internalNotes  Optional internal notes related to the ban.
     * @param  array<string, mixed>  $metadata  Additional metadata to associate with the ban (optional).
     * @return Ban Returns the created Ban model instance.
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
        // Use the EnforcementWriter service to issue a ban to the specified account and IP address with the provided details.
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
     * Issue a ban to a specified network.
     *
     * @param  string  $cidr  The CIDR notation representing the network to be banned.
     * @param  string|null  $reason  An optional reason for issuing the ban.
     * @param  DateTimeInterface|null  $expiresAt  An optional expiration date for the ban.
     * @param  Model|null  $moderator  An optional model representing the moderator issuing the ban.
     * @param  string|null  $category  An optional category for the ban.
     * @param  string|null  $internalNotes  Optional internal notes related to the ban.
     * @param  array<string, mixed>  $metadata  Additional metadata to associate with the ban (optional).
     * @return Ban Returns the created Ban model instance.
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
        // Use the EnforcementWriter service to issue a ban to the specified network with the provided details.
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
     * Issue a ban to a specified device.
     *
     * @param  string  $deviceFingerprint  The fingerprint of the device to be banned.
     * @param  string|null  $reason  An optional reason for issuing the ban.
     * @param  DateTimeInterface|null  $expiresAt  An optional expiration date for the ban.
     * @param  Model|null  $moderator  An optional model representing the moderator issuing the ban.
     * @param  string|null  $category  An optional category for the ban.
     * @param  string|null  $internalNotes  Optional internal notes related to the ban.
     * @param  array<string, mixed>  $metadata  Additional metadata to associate with the ban (optional).
     * @return Ban Returns the created Ban model instance.
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
        // Use the EnforcementWriter service to issue a ban to the specified device with the provided details.
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
     * Issue a ban to a specified account, device, and IP address.
     *
     * @param  Model  $account  The model representing the account to be banned.
     * @param  string  $ipAddress  The IP address to be banned.
     * @param  string  $deviceFingerprint  The fingerprint of the device to be banned.
     * @param  string|null  $reason  An optional reason for issuing the ban.
     * @param  DateTimeInterface|null  $expiresAt  An optional expiration date for the ban.
     * @param  Model|null  $moderator  An optional model representing the moderator issuing the ban.
     * @param  string|null  $category  An optional category for the ban.
     * @param  string|null  $internalNotes  Optional internal notes related to the ban.
     * @param  array<string, mixed>  $metadata  Additional metadata to associate with the ban (optional).
     * @return Ban Returns the created Ban model instance.
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
        // Use the EnforcementWriter service to issue a ban to the specified account, device, and IP address with the provided details.
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
     * Resolve the active ban for a given enforcement context, which may include an account, IP address, and device fingerprint.
     *
     * @param  EnforcementContext  $context  The context containing the account, IP address, and device fingerprint to check for active bans.
     * @return Ban|null Returns the active Ban model instance if found, or null if no active ban exists for the provided context.
     */
    public function resolveActiveBan(EnforcementContext $context): ?Ban
    {
        /** @var class-string<Ban> $modelClass */
        $modelClass = config('exile.models.ban', Ban::class);

        // Check for an active ban associated with the account in the provided context, if an account is present.
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

        // Check for an active ban associated with the IP address in the provided context, if an IP address is present.
        if ($context->ipAddress !== null) {
            $ipHash = $this->hasher->hashIp($context->ipAddress);

            // Query the bans table for an active ban associated with the hashed IP address, considering various ban types that include IP bans.
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

        // Check for an active ban associated with the device fingerprint in the provided context, if a device fingerprint is present.
        if ($context->deviceFingerprint !== null) {
            $deviceHash = $this->hasher->hashDevice($context->deviceFingerprint);

            // Query the bans table for an active ban associated with the hashed device fingerprint, considering various ban types that include device bans.
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
     * Check if a specified account is currently banned, optionally considering the provided IP address and device fingerprint.
     *
     * @param  Model  $account  The model representing the account to check for bans.
     * @param  string|null  $ipAddress  An optional IP address to consider when checking for bans.
     * @param  string|null  $deviceFingerprint  An optional device fingerprint to consider when checking for bans.
     * @return bool Returns true if the account is currently banned, false otherwise.
     */
    public function isBanned(Model $account, ?string $ipAddress = null, ?string $deviceFingerprint = null): bool
    {
        // Create an EnforcementContext instance with the provided account, IP address, and device fingerprint.
        return $this->resolveActiveBan(new EnforcementContext($account, $ipAddress, $deviceFingerprint)) !== null;
    }

    /**
     * Issue a restriction to a specified account.
     *
     * @param  Model  $account  The model representing the account to which the restriction will be issued.
     * @param  RestrictionType  $type  The type of restriction to be applied (e.g., Posting, ReadOnly).
     * @param  string|null  $reason  An optional reason for issuing the restriction.
     * @param  DateTimeInterface|null  $expiresAt  An optional expiration date for the restriction.
     * @param  Model|null  $moderator  An optional model representing the moderator issuing the restriction.
     * @param  string|null  $internalNotes  Optional internal notes related to the restriction.
     * @param  array<string, mixed>  $metadata  Additional metadata to associate with the restriction (optional).
     * @return Restriction Returns the created Restriction model instance.
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
        // Use the EnforcementWriter service to issue a restriction to the specified account with the provided details.
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
     * Retrieve the active restriction for a given account and restriction type.
     *
     * @param  Model  $account  The model representing the account to check for restrictions.
     * @param  RestrictionType  $type  The type of restriction to check for (e.g., Posting, ReadOnly).
     * @return Restriction|null Returns the active Restriction model instance if found, or null if no active restriction exists.
     */
    public function activeRestrictionFor(Model $account, RestrictionType $type): ?Restriction
    {
        /** @var class-string<Restriction> $modelClass */
        $modelClass = config('exile.models.restriction', Restriction::class);

        // Determine the types of restrictions to check for based on the provided restriction type. If the type is Posting, also include ReadOnly restrictions in the query.
        $types = match ($type) {
            RestrictionType::Posting => [RestrictionType::Posting->value, RestrictionType::ReadOnly->value],
            default => [$type->value],
        };

        // Query the restrictions table to find the latest active restriction for the given account and restriction types. The query filters by the account's morph class and ID, as well as the specified restriction types, and retrieves the most recent restriction based on the ID.
        $restriction = $modelClass::query()
            ->active()
            ->where('restrictable_type', $account->getMorphClass())
            ->where('restrictable_id', $account->getKey())
            ->whereIn('type', $types)
            ->latest('id')
            ->first();

        // Return the active Restriction model instance if found, or null if no active restriction exists.
        return $restriction instanceof Restriction ? $restriction : null;
    }

    /**
     * Check if a specified account is currently restricted for a given restriction type.
     *
     * @param  Model  $account  The model representing the account to check for restrictions.
     * @param  RestrictionType  $type  The type of restriction to check for (e.g., Posting, ReadOnly).
     * @return bool Returns true if the account has an active restriction of the specified type, false otherwise.
     */
    public function isRestricted(Model $account, RestrictionType $type): bool
    {
        return $this->activeRestrictionFor($account, $type) !== null;
    }

    /**
     * Issue a strike to a specified account.
     *
     * @param  Model  $account  The model representing the account to which the strike will be issued.
     * @param  string  $reason  The reason for issuing the strike.
     * @param  int|null  $points  The number of points associated with the strike (optional).
     * @param  string|null  $category  An optional category for the strike.
     * @param  DateTimeInterface|null  $expiresAt  An optional expiration date for the strike.
     * @param  Model|null  $moderator  An optional model representing the moderator issuing the strike.
     * @param  array<string, mixed>  $metadata  Additional metadata to associate with the strike (optional).
     * @return Strike Returns the created Strike model instance.
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
        // Use the EnforcementWriter service to issue a strike to the specified account with the provided details.
        $strike = $this->writer->issueStrike(
            account: $account,
            reason: $reason,
            points: $points ?? (int) config('exile.strikes.default_points', 1),
            category: $category,
            expiresAt: $expiresAt,
            moderator: $moderator,
            metadata: $metadata,
        );

        // After issuing the strike, evaluate the account's escalation status using the EscalationEngine service to determine if any further actions are required based on the accumulated strikes.
        $this->escalation->evaluate($account);

        // Return the created Strike model instance to the caller.
        return $strike;
    }

    /**
     * Issue a warning to a specified account.
     *
     * @param  Model  $account  The model representing the account to which the warning will be issued.
     * @param  string  $reason  The reason for issuing the warning.
     * @param  WarningSeverity  $severity  The severity level of the warning (default is Medium).
     * @param  string|null  $category  An optional category for the warning.
     * @param  string|null  $internalNotes  Optional internal notes related to the warning.
     * @param  Model|null  $moderator  An optional model representing the moderator issuing the warning.
     * @param  array<string, mixed>  $metadata  Additional metadata to associate with the warning (optional).
     * @return Warning Returns the created Warning model instance.
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
        // Use the EnforcementWriter service to issue a warning to the specified account with the provided details.
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
     * Calculate the total active strike points for a given account.
     *
     * @param  Model  $account  The model representing the account for which to calculate active strike points.
     * @return int Returns the total number of active strike points for the specified account.
     */
    public function activeStrikePoints(Model $account): int
    {
        /** @var class-string<Strike> $modelClass */
        $modelClass = config('exile.models.strike', Strike::class);

        // Calculate the total active strike points for the given account by querying the strikes table and summing the points of all active strikes associated with the account.
        return (int) $modelClass::query()
            ->active()
            ->where('strikeable_type', $account->getMorphClass())
            ->where('strikeable_id', $account->getKey())
            ->sum('points');
    }

    /**
     * Register a device fingerprint for a given account.
     *
     * @param  Model  $account  The model representing the account to which the device fingerprint will be registered.
     * @param  string  $fingerprint  The device fingerprint to be registered.
     * @param  string|null  $ipAddress  The IP address associated with the device (optional).
     * @param  string|null  $label  A label for the device (optional).
     * @param  array<string, mixed>  $metadata  Additional metadata to associate with the device fingerprint (optional).
     * @return DeviceFingerprint Returns the created or updated DeviceFingerprint model instance.
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

        // Update the device fingerprint's last IP hash, label, metadata, and last_seen_at timestamp. Save the changes to the database.
        $device->forceFill([
            'last_ip_hash' => $ipAddress !== null ? $this->hasher->hashIp($ipAddress) : $device->last_ip_hash,
            'label' => $label ?? $device->label,
            'metadata' => $metadata !== [] ? $metadata : $device->metadata,
            'last_seen_at' => now(),
        ])->save();

        // Log the registration of the device fingerprint for auditing purposes, including the device ID, account ID, and any provided label.
        $this->audit->log('device.seen', $device, $account, ['label' => $label]);

        // Return the created or updated DeviceFingerprint model instance to the caller.
        return $device;
    }

    /**
     * Submit a ban appeal for a given ban.
     *
     * @param  Ban  $ban  The ban for which the appeal is being submitted.
     * @param  Model  $appellant  The model representing the user submitting the appeal.
     * @param  string  $message  The message provided by the appellant explaining their appeal.
     * @return BanAppeal Returns the created BanAppeal model instance.
     *
     * @throws LogicException If ban appeals are disabled or if a pending appeal already exists for this ban.
     * @throws InvalidArgumentException If the appeal message is empty or exceeds the maximum allowed length.
     */
    public function submitAppeal(Ban $ban, Model $appellant, string $message): BanAppeal
    {
        // Check if ban appeals are enabled in the configuration. If they are disabled, throw a LogicException indicating that ban appeals are not allowed.
        if (! config('exile.appeals.enabled', true)) {
            throw new LogicException('Ban appeals are disabled.');
        }

        // Trim the appeal message to remove any leading or trailing whitespace and retrieve the maximum allowed length for appeal messages from the configuration, defaulting to 3000 characters if not set.
        $message = trim($message);
        $maxLength = (int) config('exile.appeals.max_message_length', 3000);

        // Check if the appeal message is empty or exceeds the maximum allowed length. If either condition is true, throw an InvalidArgumentException indicating that the appeal message must contain between 1 and the maximum number of characters.
        if ($message === '' || mb_strlen($message) > $maxLength) {
            throw new InvalidArgumentException("Appeal messages must contain between 1 and {$maxLength} characters.");
        }

        // Check if multiple pending appeals are allowed in the configuration. If they are not allowed and a pending appeal already exists for this ban, throw a LogicException indicating that a pending appeal already exists for this ban.
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

        // Trigger the AppealSubmitted event to notify listeners that a new appeal has been submitted, and log the submission action in the audit log with the appropriate details.
        event(new AppealSubmitted($appeal));
        $this->audit->log('appeal.submitted', $appeal, $appellant, ['ban_id' => $ban->getKey()]);

        // Return the created BanAppeal model instance to the caller.
        return $appeal;
    }

    /**
     * Resolve a pending appeal by approving or denying it.
     *
     * @param  BanAppeal  $appeal  The appeal to be resolved.
     * @param  AppealStatus  $status  The status to set for the appeal (Approved or Denied).
     * @param  Model  $reviewer  The model representing the user who is resolving the appeal.
     * @param  string|null  $response  An optional response message from the reviewer.
     * @return bool Returns true if the appeal was successfully resolved, false otherwise.
     *
     * @throws LogicException If the appeal is not in a pending state and cannot be resolved.
     * @throws InvalidArgumentException If the provided status is not Approved or Denied.
     */
    public function resolveAppeal(
        BanAppeal $appeal,
        AppealStatus $status,
        Model $reviewer,
        ?string $response = null,
    ): bool {
        // Check if the appeal is in a pending state. If it is not, throw a LogicException indicating that only pending appeals can be resolved.
        if (! $appeal->isPending()) {
            throw new LogicException('Only pending appeals may be resolved.');
        }

        // Check if the provided status is either Approved or Denied. If it is not, throw an InvalidArgumentException indicating that only these two statuses are valid for resolving an appeal.
        if (! in_array($status, [AppealStatus::Approved, AppealStatus::Denied], true)) {
            throw new InvalidArgumentException('An appeal may only be approved or denied by a reviewer.');
        }

        // Update the appeal's status, response, reviewer information, and reviewed_at timestamp. Save the changes to the database.
        $saved = $appeal->forceFill([
            'status' => $status,
            'response' => $response,
            'reviewed_by_type' => $reviewer->getMorphClass(),
            'reviewed_by_id' => $reviewer->getKey(),
            'reviewed_at' => now(),
        ])->save();

        // If the appeal was not successfully saved, return false to indicate that the resolution failed.
        if (! $saved) {
            return false;
        }

        // If the appeal was approved, revoke the associated ban using the EnforcementWriter service.
        if ($status === AppealStatus::Approved) {
            $this->writer->revokeBan($appeal->ban, $reviewer);
        }

        // Trigger the AppealResolved event to notify listeners that the appeal has been resolved, and log the resolution action in the audit log with the appropriate details.
        event(new AppealResolved($appeal));
        $this->audit->log('appeal.resolved', $appeal, $reviewer, ['status' => $status->value]);

        // Return true to indicate that the appeal was successfully resolved.
        return true;
    }

    /**
     * Withdraw a pending appeal.
     *
     * @param  BanAppeal  $appeal  The appeal to be withdrawn.
     * @param  Model  $appellant  The model representing the user who is withdrawing the appeal.
     * @return bool Returns true if the appeal was successfully withdrawn, false otherwise.
     *
     * @throws LogicException If the appeal is not in a pending state and cannot be withdrawn.
     */
    public function withdrawAppeal(BanAppeal $appeal, Model $appellant): bool
    {
        // Check if the appeal is in a pending state. If it is not, throw a LogicException indicating that only pending appeals can be withdrawn.
        if (! $appeal->isPending()) {
            throw new LogicException('Only pending appeals may be withdrawn.');
        }

        // Update the appeal's status to "Withdrawn" and set the reviewed_at timestamp to the current time. Save the changes to the database.
        $saved = $appeal->forceFill([
            'status' => AppealStatus::Withdrawn,
            'reviewed_at' => now(),
        ])->save();

        // If the appeal was successfully saved, log the withdrawal action in the audit log with the appropriate details.
        if ($saved) {
            $this->audit->log('appeal.withdrawn', $appeal, $appellant);
        }

        // Return the result of the save operation, indicating whether the appeal was successfully withdrawn.
        return $saved;
    }

    /**
     * Attach an existing evidence file to the specified subject.
     *
     * @param  Model  $subject  The model to which the evidence will be attached.
     * @param  string  $disk  The name of the disk where the evidence file is stored.
     * @param  string  $path  The path to the evidence file on the specified disk.
     * @param  string|null  $originalName  The original name of the evidence file (optional).
     * @param  string|null  $mimeType  The MIME type of the evidence file (optional).
     * @param  int|null  $sizeBytes  The size of the evidence file in bytes (optional).
     * @param  Model|null  $uploadedBy  The model representing the user who uploaded the evidence (optional).
     * @param  array<string, mixed>  $metadata  Additional metadata to associate with the evidence (optional).
     * @param  string|null  $checksumSha256  The SHA-256 checksum of the evidence file (optional).
     * @return Evidence Returns the created Evidence model instance.
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
        ?string $checksumSha256 = null,
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
            'checksum_sha256' => $checksumSha256,
            'metadata' => $metadata,
            'uploaded_by_type' => $uploadedBy?->getMorphClass(),
            'uploaded_by_id' => $uploadedBy?->getKey(),
        ]);

        // Log the attachment of the evidence file for auditing purposes, including the evidence ID and the associated model.
        $this->audit->log('evidence.attached', $subject, $uploadedBy, ['evidence_id' => $evidence->getKey()]);

        // Return the created Evidence model instance to the caller.
        return $evidence;
    }

    /**
     * Store an uploaded evidence file and attach it to the specified subject.
     *
     * @param  Model  $subject  The model to which the evidence will be attached.
     * @param  UploadedFile  $file  The uploaded file to be stored as evidence.
     * @param  Model|null  $uploadedBy  The model representing the user who uploaded the evidence (optional).
     * @param  array<string, mixed>  $metadata  Additional metadata to associate with the evidence (optional).
     * @return Evidence Returns the created Evidence model instance.
     *
     * @throws InvalidArgumentException If the uploaded file exceeds the maximum allowed size.
     * @throws LogicException If the evidence file cannot be stored or if the checksum cannot be calculated.
     */
    public function storeEvidence(
        Model $subject,
        UploadedFile $file,
        ?Model $uploadedBy = null,
        array $metadata = [],
    ): Evidence {
        // Retrieve the maximum allowed size for evidence files from the configuration, defaulting to 10 MB (10240 KB) if not set.
        $maxKilobytes = (int) config('exile.evidence.max_size_kilobytes', 10240);

        // Check if the size of the uploaded file exceeds the maximum allowed size. If it does, throw an InvalidArgumentException with a message indicating the limit.
        if ($file->getSize() > $maxKilobytes * 1024) {
            throw new InvalidArgumentException("Evidence files may not exceed {$maxKilobytes} KB.");
        }

        // Retrieve the disk and directory configuration for storing evidence files. The disk defaults to 'local' and the directory defaults to 'exile/evidence' if not set in the configuration.
        $disk = (string) config('exile.evidence.disk', 'local');
        $directory = trim((string) config('exile.evidence.directory', 'exile/evidence'), '/');
        $storedPath = $file->store($directory, $disk);

        // If the file could not be stored (i.e., $storedPath is false), throw a LogicException indicating that the evidence file could not be stored.
        if ($storedPath === false) {
            throw new LogicException('The evidence file could not be stored.');
        }

        // Use a try-catch block to calculate the checksum of the stored file and attach it as evidence. If an exception occurs during this process, delete the stored file and rethrow the exception.
        try {
            // Calculate the SHA-256 checksum of the stored file using the checksumStoredFile method.
            $checksum = $this->checksumStoredFile(
                $disk,
                $storedPath
            );

            // Attach the stored evidence file to the specified subject using the attachEvidence method, passing in the relevant parameters including the disk, path, original name, MIME type, size, uploaded by, metadata, and checksum.
            return $this->attachEvidence(
                subject: $subject,
                disk: $disk,
                path: $storedPath,
                originalName: $file->getClientOriginalName(),
                mimeType: $file->getMimeType(),
                sizeBytes: $file->getSize(),
                uploadedBy: $uploadedBy,
                metadata: $metadata,
                checksumSha256: $checksum,
            );
        } catch (\Throwable $exception) {
            // If an exception occurs during the checksum calculation or evidence attachment, delete the stored file from the specified disk and path to clean up any partially stored data.
            Storage::disk($disk)->delete($storedPath);

            // Rethrow the caught exception to propagate the error to the caller.
            throw $exception;
        }
    }

    /**
     * Calculate the SHA-256 checksum of a stored file on the specified disk.
     *
     * @param  string  $disk  The name of the disk where the file is stored.
     * @param  string  $path  The path to the file on the specified disk.
     * @return string The calculated SHA-256 checksum of the file.
     *
     * @throws LogicException If the file cannot be read or if the checksum cannot be calculated.
     */
    private function checksumStoredFile(
        string $disk,
        string $path
    ): string {
        // Open a read stream to the stored file on the specified disk and path.
        $stream = Storage::disk($disk)
            ->readStream($path);

        // If the stream could not be opened, throw a LogicException indicating that the file could not be read.
        if (! is_resource($stream)) {
            throw new LogicException(
                'The stored evidence file could not be read.'
            );
        }

        // Initialize a SHA-256 hash context for calculating the checksum of the file.
        $hash = hash_init('sha256');

        // Use a try-finally block to ensure the stream is closed after processing, even if an exception occurs.
        try {
            // Update the hash context with the contents of the file stream. If the update fails, throw a LogicException indicating that the checksum could not be calculated.
            if (hash_update_stream($hash, $stream) === false) {
                throw new LogicException(
                    'The evidence checksum could not be calculated.'
                );
            }

            // Finalize the hash calculation and return the resulting SHA-256 checksum as a string.
            return hash_final($hash);
        } finally {
            fclose($stream);
        }
    }

    /**
     * Delete an evidence record and optionally remove the associated file from storage.
     *
     * @param  Evidence  $evidence  The evidence record to delete.
     * @param  bool  $deleteFile  Whether to delete the associated file from storage (default: true).
     * @return bool Returns true if the evidence record was successfully deleted, false otherwise.
     */
    public function deleteEvidence(Evidence $evidence, bool $deleteFile = true): bool
    {
        // If the $deleteFile parameter is true, delete the associated file from storage using the specified disk and path.
        if ($deleteFile) {
            Storage::disk($evidence->disk)->delete($evidence->path);
        }

        // Log the deletion of the evidence record for auditing purposes, including the evidence ID and the associated model.
        $this->audit->log('evidence.deleted', $evidence->evidenceable, null, ['evidence_id' => $evidence->getKey()]);

        // Delete the evidence record from the database and return whether the deletion was successful.
        return (bool) $evidence->delete();
    }

    /**
     * Revoke a ban.
     *
     * @param  Ban  $ban  The ban record to revoke.
     * @param  Model|null  $moderator  The moderator performing the revocation (optional).
     * @return bool Returns true if the ban was successfully revoked, false otherwise.
     */
    public function revokeBan(Ban $ban, ?Model $moderator = null): bool
    {
        // Call the EnforcementWriter to revoke the ban and return the result.
        return $this->writer->revokeBan($ban, $moderator);
    }

    /**
     * Revoke a restriction.
     *
     * @param  Restriction  $restriction  The restriction record to revoke.
     * @param  Model|null  $moderator  The moderator performing the revocation (optional).
     * @return bool Returns true if the restriction was successfully revoked, false otherwise.
     */
    public function revokeRestriction(Restriction $restriction, ?Model $moderator = null): bool
    {
        // Call the EnforcementWriter to revoke the restriction and return the result.
        return $this->writer->revokeRestriction($restriction, $moderator);
    }

    /**
     * Revoke a strike.
     *
     * @param  Strike  $strike  The strike record to revoke.
     * @param  Model|null  $moderator  The moderator performing the revocation (optional).
     * @return bool Returns true if the strike was successfully revoked, false otherwise.
     */
    public function revokeStrike(Strike $strike, ?Model $moderator = null): bool
    {
        // Call the EnforcementWriter to revoke the strike and return the result.
        return $this->writer->revokeStrike($strike, $moderator);
    }

    /**
     * Mark a ban as expired and trigger the appropriate events and notifications.
     *
     * @param  Ban  $ban  The ban to mark as expired.
     * @return bool Returns true if the ban was successfully marked as expired, false otherwise.
     */
    public function markBanExpired(Ban $ban): bool
    {
        // If the ban is not expired or has already been notified, return false.
        if (! $ban->isExpired() || $ban->expired_notified_at !== null) {
            return false;
        }

        // Mark the ban as expired by updating the 'expired_notified_at' timestamp and save the changes.
        $saved = $ban->forceFill(['expired_notified_at' => now()])->save();

        // If the ban was successfully marked as expired, trigger the BanExpired event, log the action, and send notifications.
        if ($saved) {
            event(new BanExpired($ban));
            $this->audit->log('ban.expired', $ban);
            $this->notifications->banExpired($ban);
        }

        // Return whether the ban was successfully marked as expired.
        return $saved;
    }
}
