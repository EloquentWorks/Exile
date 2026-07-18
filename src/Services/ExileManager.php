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
 * The ExileManager class provides methods for managing bans, restrictions, strikes, and warnings within the Exile system.
 *
 * This class serves as a central point for issuing and resolving enforcement actions, as well as checking the status of accounts.
 */
final class ExileManager
{
    /**
     * Create a new instance of the ExileManager class.
     *
     * @param  EnforcementWriter  $writer  The service responsible for writing enforcement actions to the database.
     * @param  EscalationEngine  $escalation  The service responsible for handling escalation logic for bans and restrictions.
     * @param  IdentifierHasher  $hasher  The service responsible for hashing identifiers such as IP addresses and device fingerprints.
     * @param  IpMatcher  $ipMatcher  The service responsible for matching IP addresses against CIDR ranges.
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
     * Issue a ban for a specific account with the specified reason, expiration date, moderator, category, internal notes, and metadata.
     *
     * @param  Model  $account  The account to be banned.
     * @param  string|null  $reason  The reason for the ban (optional).
     * @param  DateTimeInterface|null  $expiresAt  The expiration date of the ban (optional).
     * @param  Model|null  $moderator  The moderator issuing the ban (optional).
     * @param  string|null  $category  An optional category for the ban.
     * @param  string|null  $internalNotes  Optional internal notes related to the ban.
     * @param  array<string, mixed>  $metadata  Optional metadata associated with the ban.
     * @return Ban Returns the created Ban instance.
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
        // Use the EnforcementWriter service to issue a ban for the specified account with the provided reason, expiration date, moderator, category, internal notes, and metadata.
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
     * Issue a ban for a specific IP address with the specified reason, expiration date, moderator, category, internal notes, and metadata.
     *
     * @param  string  $ipAddress  The IP address to be banned.
     * @param  string|null  $reason  The reason for the ban (optional).
     * @param  DateTimeInterface|null  $expiresAt  The expiration date of the ban (optional).
     * @param  Model|null  $moderator  The moderator issuing the ban (optional).
     * @param  string|null  $category  An optional category for the ban.
     * @param  string|null  $internalNotes  Optional internal notes related to the ban.
     * @param  array<string, mixed>  $metadata  Optional metadata associated with the ban.
     * @return Ban Returns the created Ban instance.
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
        // Use the EnforcementWriter service to issue a ban for the specified IP address with the provided reason, expiration date, moderator, category, internal notes, and metadata.
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
     * Issue a ban for both an account and an IP address with the specified reason, expiration date, moderator, category, internal notes, and metadata.
     *
     * @param  Model  $account  The account to be banned.
     * @param  string  $ipAddress  The IP address to be banned.
     * @param  string|null  $reason  The reason for the ban (optional).
     * @param  DateTimeInterface|null  $expiresAt  The expiration date of the ban (optional).
     * @param  Model|null  $moderator  The moderator issuing the ban (optional).
     * @param  string|null  $category  An optional category for the ban.
     * @param  string|null  $internalNotes  Optional internal notes related to the ban.
     * @param  array<string, mixed>  $metadata  Optional metadata associated with the ban.
     * @return Ban Returns the created Ban instance.
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
        // Use the EnforcementWriter service to issue a ban for both the specified account and IP address with the provided reason, expiration date, moderator, category, internal notes, and metadata.
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
     * Issue a ban for a specific network with the specified reason, expiration date, moderator, category, internal notes, and metadata.
     *
     * @param  string  $cidr  The network CIDR to be banned.
     * @param  string|null  $reason  The reason for the ban (optional).
     * @param  DateTimeInterface|null  $expiresAt  The expiration date of the ban (optional).
     * @param  Model|null  $moderator  The moderator issuing the ban (optional).
     * @param  string|null  $category  An optional category for the ban.
     * @param  string|null  $internalNotes  Optional internal notes related to the ban.
     * @param  array<string, mixed>  $metadata  Optional metadata associated with the ban.
     * @return Ban Returns the created Ban instance.
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
        // Use the EnforcementWriter service to issue a ban for the specified network CIDR with the provided reason, expiration date, moderator, category, internal notes, and metadata.
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
     * Issue a ban for a specific device fingerprint with the specified reason, expiration date, moderator, category, internal notes, and metadata.
     *
     * @param  string  $deviceFingerprint  The device fingerprint to be banned.
     * @param  string|null  $reason  The reason for the ban (optional).
     * @param  DateTimeInterface|null  $expiresAt  The expiration date of the ban (optional).
     * @param  Model|null  $moderator  The moderator issuing the ban (optional).
     * @param  string|null  $category  An optional category for the ban.
     * @param  string|null  $internalNotes  Optional internal notes related to the ban.
     * @param  array<string, mixed>  $metadata  Optional metadata associated with the ban.
     * @return Ban Returns the created Ban instance.
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
        // Use the EnforcementWriter service to issue a ban for the specified device fingerprint with the provided reason, expiration date, moderator, category, internal notes, and metadata.
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
     * Issue a combined ban for an account, IP address, and device fingerprint with the specified reason, expiration date, moderator, category, internal notes, and metadata.
     *
     * @param  Model  $account  The account to be banned.
     * @param  string  $ipAddress  The IP address to be banned.
     * @param  string  $deviceFingerprint  The device fingerprint to be banned.
     * @param  string|null  $reason  The reason for the ban (optional).
     * @param  DateTimeInterface|null  $expiresAt  The expiration date of the ban (optional).
     * @param  Model|null  $moderator  The moderator issuing the ban (optional).
     * @param  string|null  $category  An optional category for the ban.
     * @param  string|null  $internalNotes  Optional internal notes related to the ban.
     * @param  array<string, mixed>  $metadata  Optional metadata associated with the ban.
     * @return Ban Returns the created Ban instance.
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
        // Use the EnforcementWriter service to issue a combined ban for the specified account, IP address, and device fingerprint with the provided reason, expiration date, moderator, category, internal notes, and metadata.
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
     * Resolve the active ban that matches the supplied enforcement context.
     *
     * Combined bans use the behavior configured by
     * exile.security.combined_ban_match:
     *
     * - any: any identifier stored by a combined ban may match.
     * - all: every identifier required by the combined ban must match.
     *
     * @param  EnforcementContext  $context  The context containing the account, IP address, and device fingerprint to check against active bans.
     * @return Ban|null Returns the active Ban instance if a match is found, or null if no active ban matches the provided context.
     */
    public function resolveActiveBan(EnforcementContext $context): ?Ban
    {
        // Determine the matching mode for combined bans from the configuration, defaulting to 'any' if not specified.
        $mode = (string) config(
            'exile.security.combined_ban_match',
            'any'
        );

        // Use a match expression to resolve the active ban based on the configured matching mode.
        return match ($mode) {
            'any' => $this->resolveActiveBanUsingAnyMatch($context),
            'all' => $this->resolveActiveBanUsingAllMatch($context),
            default => throw new InvalidArgumentException(
                "Invalid Exile combined-ban matching mode [{$mode}]. "
                .'Supported values are [any] and [all].'
            ),
        };
    }

    /**
     * Resolve a ban when combined bans match any stored identifier.
     *
     * This preserves Exile's original behavior.
     *
     * @param  EnforcementContext  $context  The context containing the account, IP address, and device fingerprint to check against active bans.
     * @return Ban|null Returns the active Ban instance if a match is found, or null if no active ban matches the provided context.
     */
    private function resolveActiveBanUsingAnyMatch(
        EnforcementContext $context
    ): ?Ban {
        /** @var class-string<Ban> $modelClass */
        $modelClass = config(
            'exile.models.ban',
            Ban::class
        );

        // Check for an active ban that matches the account, IP address, or device fingerprint
        if ($context->account !== null) {
            // Query the database for an active ban that matches the account
            $accountBan = $modelClass::query()
                ->active()
                ->where(
                    'bannable_type',
                    $context->account->getMorphClass()
                )
                ->where(
                    'bannable_id',
                    $context->account->getKey()
                )
                ->whereIn('type', [
                    BanType::Account->value,
                    BanType::AccountAndIp->value,
                    BanType::AccountDeviceAndIp->value,
                ])
                ->latest('id')
                ->first();

            // If a matching account ban is found, return it
            if ($accountBan instanceof Ban) {
                return $accountBan;
            }
        }

        // Check for an active ban that matches the IP address
        if ($context->ipAddress !== null) {
            // Hash the IP address for comparison with stored bans
            $ipHash = $this->hasher->hashIp(
                $context->ipAddress
            );

            // Query the database for an active ban that matches the hashed IP address
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

            // If a matching IP ban is found, return it
            if ($ipBan instanceof Ban) {
                return $ipBan;
            }

            // Check for an active network ban that matches the provided IP address
            $networkBan = $this->resolveNetworkBan(
                $modelClass,
                $context->ipAddress
            );

            // If a matching network ban is found, return it
            if ($networkBan instanceof Ban) {
                return $networkBan;
            }
        }

        // Check for an active ban that matches the device fingerprint
        if ($context->deviceFingerprint !== null) {
            // Hash the device fingerprint for comparison with stored bans
            $deviceHash = $this->hasher->hashDevice(
                $context->deviceFingerprint
            );

            // Query the database for an active ban that matches the hashed device fingerprint
            $deviceBan = $modelClass::query()
                ->active()
                ->where('device_hash', $deviceHash)
                ->whereIn('type', [
                    BanType::Device->value,
                    BanType::AccountDeviceAndIp->value,
                ])
                ->latest('id')
                ->first();

            // If a matching device ban is found, return it
            if ($deviceBan instanceof Ban) {
                return $deviceBan;
            }
        }

        // If no matching ban is found,
        return null;
    }

    /**
     * Resolve a ban when combined bans require all stored identifiers to match.
     *
     * This is a more strict approach to combined bans.
     *
     * @param  EnforcementContext  $context  The context containing the account, IP address, and device fingerprint to check against active bans.
     * @return Ban|null Returns the active Ban instance if a match is found, or null if no active ban matches the provided context.
     */
    private function resolveActiveBanUsingAllMatch(
        EnforcementContext $context
    ): ?Ban {
        /** @var class-string<Ban> $modelClass */
        $modelClass = config(
            'exile.models.ban',
            Ban::class
        );

        // Check for a combined ban that requires all identifiers (account, IP address, and device fingerprint) to match
        if (
            $context->account !== null
            && $context->ipAddress !== null
            && $context->deviceFingerprint !== null
        ) {
            // Query the database for an active combined ban that matches the account, IP address, and device fingerprint
            $combinedBan = $modelClass::query()
                ->active()
                ->where(
                    'type',
                    BanType::AccountDeviceAndIp->value
                )
                ->where(
                    'bannable_type',
                    $context->account->getMorphClass()
                )
                ->where(
                    'bannable_id',
                    $context->account->getKey()
                )
                ->where(
                    'ip_hash',
                    $this->hasher->hashIp(
                        $context->ipAddress
                    )
                )
                ->where(
                    'device_hash',
                    $this->hasher->hashDevice(
                        $context->deviceFingerprint
                    )
                )
                ->latest('id')
                ->first();

            // If a matching combined ban is found, return it
            if ($combinedBan instanceof Ban) {
                return $combinedBan;
            }
        }

        // Check for a combined ban that requires both the account and IP address to match
        if (
            $context->account !== null
            && $context->ipAddress !== null
        ) {
            // Query the database for an active combined ban that matches the account and IP address
            $combinedBan = $modelClass::query()
                ->active()
                ->where(
                    'type',
                    BanType::AccountAndIp->value
                )
                ->where(
                    'bannable_type',
                    $context->account->getMorphClass()
                )
                ->where(
                    'bannable_id',
                    $context->account->getKey()
                )
                ->where(
                    'ip_hash',
                    $this->hasher->hashIp(
                        $context->ipAddress
                    )
                )
                ->latest('id')
                ->first();

            // If a matching combined ban is found, return it
            if ($combinedBan instanceof Ban) {
                return $combinedBan;
            }
        }

        // Check for a combined ban that requires both the account and device fingerprint to match
        if ($context->account !== null) {
            // Query the database for an active combined ban that matches the account and device fingerprint
            $accountBan = $modelClass::query()
                ->active()
                ->where(
                    'type',
                    BanType::Account->value
                )
                ->where(
                    'bannable_type',
                    $context->account->getMorphClass()
                )
                ->where(
                    'bannable_id',
                    $context->account->getKey()
                )
                ->latest('id')
                ->first();

            // If a matching account ban is found, return it
            if ($accountBan instanceof Ban) {
                return $accountBan;
            }
        }

        // Check for a combined ban that requires the IP address to match
        if ($context->ipAddress !== null) {
            // Query the database for an active IP ban that matches the provided IP address
            $ipBan = $modelClass::query()
                ->active()
                ->where(
                    'type',
                    BanType::Ip->value
                )
                ->where(
                    'ip_hash',
                    $this->hasher->hashIp(
                        $context->ipAddress
                    )
                )
                ->latest('id')
                ->first();

            // If a matching IP ban is found, return it
            if ($ipBan instanceof Ban) {
                return $ipBan;
            }

            // Check for a network ban that matches the provided IP address
            $networkBan = $this->resolveNetworkBan(
                $modelClass,
                $context->ipAddress
            );

            // If a matching network ban is found, return it
            if ($networkBan instanceof Ban) {
                return $networkBan;
            }
        }

        // Check for a combined ban that requires the device fingerprint to match
        if ($context->deviceFingerprint !== null) {
            $deviceBan = $modelClass::query()
                ->active()
                ->where(
                    'type',
                    BanType::Device->value
                )
                ->where(
                    'device_hash',
                    $this->hasher->hashDevice(
                        $context->deviceFingerprint
                    )
                )
                ->latest('id')
                ->first();

            // If a matching device ban is found, return it
            if ($deviceBan instanceof Ban) {
                return $deviceBan;
            }
        }

        // If no matching ban is found, return null
        return null;
    }

    /**
     * Resolve an active CIDR network ban for an IP address.
     *
     * @param  class-string<Ban>  $modelClass
     */
    private function resolveNetworkBan(
        string $modelClass,
        string $ipAddress
    ): ?Ban {
        // Query the database for an active network ban that matches the provided IP address using CIDR notation
        $networkBan = $modelClass::query()
            ->active()
            ->where(
                'type',
                BanType::Network->value
            )
            ->whereNotNull('cidr')
            ->get()
            ->first(
                fn (Ban $ban): bool => $ban->cidr !== null
                    && $this->ipMatcher->contains(
                        $ban->cidr,
                        $ipAddress
                    )
            );

        // If a matching network ban is found, return it; otherwise, return null
        return $networkBan instanceof Ban
            ? $networkBan
            : null;
    }

    /**
     * Check if a given account is currently banned based on the provided enforcement context.
     *
     * @param  Model  $account  The account to check for an active ban.
     * @param  string|null  $ipAddress  Optional IP address to check against active bans (default is null).
     * @param  string|null  $deviceFingerprint  Optional device fingerprint to check against active bans (default is null).
     * @return bool Returns true if the account is currently banned, false otherwise.
     */
    public function isBanned(Model $account, ?string $ipAddress = null, ?string $deviceFingerprint = null): bool
    {
        // Create an EnforcementContext instance with the provided account, IP address, and device fingerprint
        return $this->resolveActiveBan(new EnforcementContext($account, $ipAddress, $deviceFingerprint)) !== null;
    }

    /**
     * Issue a restriction for a given account with the specified type, reason, expiration date, moderator, internal notes, and metadata.
     *
     * @param  Model  $account  The account for which the restriction is being issued.
     * @param  RestrictionType  $type  The type of restriction to issue.
     * @param  string|null  $reason  The reason for issuing the restriction (optional).
     * @param  DateTimeInterface|null  $expiresAt  The expiration date of the restriction (optional).
     * @param  Model|null  $moderator  The moderator issuing the restriction (optional).
     * @param  string|null  $internalNotes  Optional internal notes related to the restriction.
     * @param  array<string, mixed>  $metadata  Optional metadata associated with the restriction.
     * @return Restriction Returns the created Restriction instance.
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
        // Use the EnforcementWriter service to issue a restriction for the specified account with the provided type, reason, expiration date, moderator, internal notes, and metadata.
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
     * Get the active restriction for a given account and restriction type.
     *
     * @param  Model  $account  The account for which to retrieve the active restriction.
     * @param  RestrictionType  $type  The type of restriction to check for.
     * @return Restriction|null Returns the active Restriction instance if found, or null if no active restriction exists.
     */
    public function activeRestrictionFor(Model $account, RestrictionType $type): ?Restriction
    {
        /** @var class-string<Restriction> $modelClass */
        $modelClass = config('exile.models.restriction', Restriction::class);

        // Determine the types of restrictions to check based on the provided restriction type.
        $types = match ($type) {
            RestrictionType::Posting => [RestrictionType::Posting->value, RestrictionType::ReadOnly->value],
            default => [$type->value],
        };

        // Query the database for an active restriction that matches the account and the specified restriction types.
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
     * Check if a given account is currently restricted based on the provided restriction type.
     *
     * @param  Model  $account  The account to check for an active restriction.
     * @param  RestrictionType  $type  The type of restriction to check for.
     * @return bool Returns true if the account is currently restricted, false otherwise.
     */
    public function isRestricted(Model $account, RestrictionType $type): bool
    {
        // Check if there is an active restriction for the given account and restriction type.
        return $this->activeRestrictionFor($account, $type) !== null;
    }

    /**
     * Issue a strike for a given account with the specified reason, points, category, expiration date, moderator, and metadata.
     *
     * @param  Model  $account  The account for which the strike is being issued.
     * @param  string  $reason  The reason for issuing the strike.
     * @param  int|null  $points  The number of points for the strike (optional).
     * @param  string|null  $category  An optional category for the strike.
     * @param  DateTimeInterface|null  $expiresAt  The expiration date of the strike (optional).
     * @param  Model|null  $moderator  The moderator issuing the strike (optional).
     * @param  array<string, mixed>  $metadata  Optional metadata associated with the strike.
     * @return Strike Returns the created Strike instance.
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
        // Use the EnforcementWriter service to issue a strike for the specified account with the provided reason, points, category, expiration date, moderator, and metadata.
        $strike = $this->writer->issueStrike(
            account: $account,
            reason: $reason,
            points: $points ?? (int) config('exile.strikes.default_points', 1),
            category: $category,
            expiresAt: $expiresAt,
            moderator: $moderator,
            metadata: $metadata,
        );

        // After issuing the strike, evaluate any potential escalations based on the new strike for the specified account.
        $this->escalation->evaluate($account);

        // Return the created strike instance after evaluating any potential escalations based on the new strike.
        return $strike;
    }

    /**
     * Issue a warning for a given account with the specified reason, severity, category, internal notes, moderator, and metadata.
     *
     * @param  Model  $account  The account for which the warning is being issued.
     * @param  string  $reason  The reason for issuing the warning.
     * @param  WarningSeverity  $severity  The severity level of the warning (default is Medium).
     * @param  string|null  $category  An optional category for the warning.
     * @param  string|null  $internalNotes  Optional internal notes related to the warning.
     * @param  Model|null  $moderator  The moderator issuing the warning (optional).
     * @param  array<string, mixed>  $metadata  Optional metadata associated with the warning.
     * @return Warning Returns the created Warning instance.
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
        // Use the EnforcementWriter service to issue a warning for the specified account with the provided reason, severity, category, internal notes, moderator, and metadata.
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
     * @param  Model  $account  The account for which to calculate active strike points.
     * @return int Returns the total active strike points for the account.
     */
    public function activeStrikePoints(Model $account): int
    {
        /** @var class-string<Strike> $modelClass */
        $modelClass = config('exile.models.strike', Strike::class);

        // Calculate the total active strike points for the given account by summing the 'points' column of active strikes associated with the account.
        return (int) $modelClass::query()
            ->active()
            ->where('strikeable_type', $account->getMorphClass())
            ->where('strikeable_id', $account->getKey())
            ->sum('points');
    }

    /**
     * Register a device fingerprint for a given account, along with optional IP address, label, and metadata.
     *
     * @param  Model  $account  The account for which the device fingerprint is being registered.
     * @param  string  $fingerprint  The device fingerprint to be registered.
     * @param  string|null  $ipAddress  The optional IP address associated with the device fingerprint.
     * @param  string|null  $label  An optional label for the device fingerprint.
     * @param  array<string, mixed>  $metadata  Optional metadata associated with the device fingerprint.
     * @return DeviceFingerprint Returns the registered or updated DeviceFingerprint instance.
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

        // If the device fingerprint record does not exist, set the first seen timestamp to the current time.
        if (! $device->exists) {
            $device->first_seen_at = now();
        }

        // Update the device fingerprint record with the provided IP address, label, metadata, and last seen timestamp.
        $device->forceFill([
            'last_ip_hash' => $ipAddress !== null ? $this->hasher->hashIp($ipAddress) : $device->last_ip_hash,
            'label' => $label ?? $device->label,
            'metadata' => $metadata !== [] ? $metadata : $device->metadata,
            'last_seen_at' => now(),
        ])->save();

        // Log the device registration or update action for auditing purposes, including the device, account, and optional label.
        $this->audit->log('device.seen', $device, $account, ['label' => $label]);

        // Notify moderators or administrators about the new device registration or update, including the device and associated account information.
        return $device;
    }

    /**
     * Submit a ban appeal for a given ban.
     *
     * @param  Ban  $ban  The ban for which the appeal is being submitted.
     * @param  Model  $appellant  The user or entity submitting the appeal.
     * @param  string  $message  The message explaining the appeal.
     * @return BanAppeal Returns the created BanAppeal instance.
     *
     * @throws LogicException If ban appeals are disabled or if a pending appeal already exists for the ban.
     * @throws InvalidArgumentException If the appeal message is empty or exceeds the maximum allowed length.
     */
    public function submitAppeal(Ban $ban, Model $appellant, string $message): BanAppeal
    {
        // Check if ban appeals are enabled in the configuration
        if (! config('exile.appeals.enabled', true)) {
            throw new LogicException('Ban appeals are disabled.');
        }

        // Trim the appeal message and retrieve the maximum allowed length from configuration
        $message = trim($message);
        $maxLength = (int) config('exile.appeals.max_message_length', 3000);

        // Validate the appeal message length to ensure it meets the configured requirements
        if ($message === '' || mb_strlen($message) > $maxLength) {
            throw new InvalidArgumentException("Appeal messages must contain between 1 and {$maxLength} characters.");
        }

        // Check if multiple pending appeals are allowed for the same ban
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

        // Log the appeal submission action for auditing purposes, including the appeal, appellant, and associated ban ID.
        event(new AppealSubmitted($appeal));
        $this->audit->log('appeal.submitted', $appeal, $appellant, ['ban_id' => $ban->getKey()]);

        // Notify moderators or administrators about the new appeal submission, including the appeal and associated ban information.
        return $appeal;
    }

    /**
     * Resolve a pending ban appeal.
     *
     * @param  BanAppeal  $appeal  The appeal to be resolved.
     * @param  AppealStatus  $status  The new status of the appeal (Approved or Denied).
     * @param  Model  $reviewer  The user or entity that is resolving the appeal.
     * @param  string|null  $response  An optional response message from the reviewer.
     * @return bool Returns true if the appeal was successfully resolved, false otherwise.
     *
     * @throws LogicException If the appeal is not pending or if the status is not valid for resolution.
     */
    public function resolveAppeal(
        BanAppeal $appeal,
        AppealStatus $status,
        Model $reviewer,
        ?string $response = null,
    ): bool {
        // Ensure that only pending appeals can be resolved
        if (! $appeal->isPending()) {
            throw new LogicException('Only pending appeals may be resolved.');
        }

        // Ensure that the status is either Approved or Denied for resolution
        if (! in_array($status, [AppealStatus::Approved, AppealStatus::Denied], true)) {
            throw new InvalidArgumentException('An appeal may only be approved or denied by a reviewer.');
        }

        // Update the appeal's status, response, reviewer information, and reviewed timestamp
        $saved = $appeal->forceFill([
            'status' => $status,
            'response' => $response,
            'reviewed_by_type' => $reviewer->getMorphClass(),
            'reviewed_by_id' => $reviewer->getKey(),
            'reviewed_at' => now(),
        ])->save();

        // If the appeal was not successfully saved, return false
        if (! $saved) {
            return false;
        }

        // If the appeal was approved, revoke the associated ban using the EnforcementWriter service
        if ($status === AppealStatus::Approved) {
            $this->writer->revokeBan($appeal->ban, $reviewer);
        }

        // Log the appeal resolution action for auditing purposes, including the appeal, reviewer, and new status.
        event(new AppealResolved($appeal));
        $this->audit->log('appeal.resolved', $appeal, $reviewer, ['status' => $status->value]);

        // Return true to indicate that the appeal was successfully resolved
        return true;
    }

    /**
     * Withdraw a pending ban appeal.
     *
     * @param  BanAppeal  $appeal  The appeal to be withdrawn.
     * @param  Model  $appellant  The user or entity that submitted the appeal.
     * @return bool Returns true if the appeal was successfully withdrawn, false otherwise.
     *
     * @throws LogicException If the appeal is not pending or if the appellant does not match the original appellant.
     */
    public function withdrawAppeal(BanAppeal $appeal, Model $appellant): bool
    {
        // Ensure that only the original appellant can withdraw their appeal
        if (! $appeal->isPending()) {
            throw new LogicException('Only pending appeals may be withdrawn.');
        }

        // Ensure that the appellant is the same as the one who submitted the appeal
        $saved = $appeal->forceFill([
            'status' => AppealStatus::Withdrawn,
            'reviewed_at' => now(),
        ])->save();

        // Log the appeal withdrawal action for auditing purposes, including the appeal and appellant information.
        if ($saved) {
            $this->audit->log('appeal.withdrawn', $appeal, $appellant);
        }

        // Return whether the appeal was successfully withdrawn
        return $saved;
    }

    /**
     * Attach an existing file as evidence for a given subject.
     *
     * @param  Model  $subject  The subject (e.g., Ban, Restriction, Strike) to which the evidence is attached.
     * @param  string  $disk  The disk where the evidence file is stored.
     * @param  string  $path  The path to the evidence file on the specified disk.
     * @param  string|null  $originalName  The original name of the evidence file. Optional.
     * @param  string|null  $mimeType  The MIME type of the evidence file. Optional.
     * @param  int|null  $sizeBytes  The size of the evidence file in bytes. Optional.
     * @param  Model|null  $uploadedBy  The user or entity that uploaded the evidence. Optional.
     * @param  array<string, mixed>  $metadata  Additional metadata to associate with the evidence. Optional.
     * @param  string|null  $checksumSha256  The SHA-256 checksum of the evidence file. Optional.
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

        // Log the evidence attachment action for auditing purposes, including the subject, uploader, and evidence ID.
        $this->audit->log('evidence.attached', $subject, $uploadedBy, ['evidence_id' => $evidence->getKey()]);

        // Return the created Evidence model instance to the caller.
        return $evidence;
    }

    /**
     * Store an uploaded file as evidence for a given subject.
     *
     * @param  Model  $subject  The subject (e.g., Ban, Restriction, Strike) to which the evidence is attached.
     * @param  UploadedFile  $file  The uploaded file to be stored as evidence.
     * @param  Model|null  $uploadedBy  The user or entity that uploaded the evidence. Optional.
     * @param  array<string, mixed>  $metadata  Additional metadata to associate with the evidence. Optional.
     * @return Evidence Returns the created Evidence model instance.
     *
     * @throws InvalidArgumentException If the file exceeds the maximum allowed size.
     * @throws LogicException If the file cannot be stored or the checksum cannot be calculated.
     */
    public function storeEvidence(
        Model $subject,
        UploadedFile $file,
        ?Model $uploadedBy = null,
        array $metadata = [],
    ): Evidence {
        // Retrieve the maximum allowed file size in kilobytes from the configuration, defaulting to 10240 KB (10 MB).
        $maxKilobytes = (int) config('exile.evidence.max_size_kilobytes', 10240);

        // Check if the uploaded file exceeds the maximum allowed size. If it does, throw an InvalidArgumentException.
        if ($file->getSize() > $maxKilobytes * 1024) {
            throw new InvalidArgumentException("Evidence files may not exceed {$maxKilobytes} KB.");
        }

        // Retrieve the disk and directory configuration for storing evidence files. Default to 'local' disk and 'exile/evidence' directory if not specified.
        $disk = (string) config('exile.evidence.disk', 'local');
        $directory = trim((string) config('exile.evidence.directory', 'exile/evidence'), '/');
        $storedPath = $file->store($directory, $disk);

        // If the file could not be stored, throw a LogicException.
        if ($storedPath === false) {
            throw new LogicException('The evidence file could not be stored.');
        }

        // Calculate the SHA-256 checksum of the stored file and attach it as evidence to the subject. If any exception occurs during this process, delete the stored file and rethrow the exception.
        try {
            // Calculate the SHA-256 checksum of the stored file.
            $checksum = $this->checksumStoredFile(
                $disk,
                $storedPath
            );

            // Attach the stored file as evidence to the subject, including relevant metadata such as the original file name, MIME type, size in bytes, and the calculated checksum.
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
            // If an exception occurs, delete the stored file to avoid orphaned files.
            Storage::disk($disk)->delete($storedPath);

            // Rethrow the exception to propagate the error.
            throw $exception;
        }
    }

    /**
     * Calculate the SHA-256 checksum of a stored file.
     *
     * @param  string  $disk  The disk where the file is stored.
     * @param  string  $path  The path to the file on the disk.
     * @return string Returns the SHA-256 checksum of the file.
     *
     * @throws LogicException If the file cannot be read or the checksum cannot be calculated.
     */
    private function checksumStoredFile(
        string $disk,
        string $path
    ): string {
        // Open a read stream to the stored file on the specified disk.
        $stream = Storage::disk($disk)
            ->readStream($path);

        // If the stream could not be opened, throw a LogicException.
        if (! is_resource($stream)) {
            throw new LogicException(
                'The stored evidence file could not be read.'
            );
        }

        // Initialize a SHA-256 hash context.
        $hash = hash_init('sha256');

        // Use a try-finally block to ensure the stream is closed after processing.
        try {
            // Update the hash context with the contents of the file stream.
            if (hash_update_stream($hash, $stream) === false) {
                throw new LogicException(
                    'The evidence checksum could not be calculated.'
                );
            }

            // Return the final SHA-256 checksum of the file.
            return hash_final($hash);
        } finally {
            fclose($stream);
        }
    }

    /**
     * Delete an evidence record and optionally its associated file.
     *
     * @param  Evidence  $evidence  The evidence record to delete.
     * @param  bool  $deleteFile  Whether to delete the associated file from storage. Defaults to true.
     * @return bool Returns true if the evidence record was successfully deleted, false otherwise.
     */
    public function deleteEvidence(Evidence $evidence, bool $deleteFile = true): bool
    {
        // If the $deleteFile flag is true, delete the associated file from storage.
        if ($deleteFile) {
            Storage::disk($evidence->disk)->delete($evidence->path);
        }

        // Log the deletion of the evidence record for auditing purposes.
        $this->audit->log('evidence.deleted', $evidence->evidenceable, null, ['evidence_id' => $evidence->getKey()]);

        // Delete the evidence record from the database and return the result.
        return (bool) $evidence->delete();
    }

    /**
     * Revoke a ban.
     *
     * @param  Ban  $ban  The ban record to revoke.
     * @param  Model|null  $moderator  The moderator performing the revocation. Optional.
     * @return bool Returns true if the ban was successfully revoked, false otherwise.
     */
    public function revokeBan(Ban $ban, ?Model $moderator = null): bool
    {
        // Delegate the revocation of the ban to the EnforcementWriter service.
        return $this->writer->revokeBan($ban, $moderator);
    }

    /**
     * Revoke a restriction.
     *
     * @param  Restriction  $restriction  The restriction record to revoke.
     * @param  Model|null  $moderator  The moderator performing the revocation. Optional.
     * @return bool Returns true if the restriction was successfully revoked, false otherwise.
     */
    public function revokeRestriction(Restriction $restriction, ?Model $moderator = null): bool
    {
        // Delegate the revocation of the restriction to the EnforcementWriter service.
        return $this->writer->revokeRestriction($restriction, $moderator);
    }

    /**
     * Revoke a strike.
     *
     * @param  Strike  $strike  The strike record to revoke.
     * @param  Model|null  $moderator  The moderator performing the revocation. Optional.
     * @return bool Returns true if the strike was successfully revoked, false otherwise.
     */
    public function revokeStrike(Strike $strike, ?Model $moderator = null): bool
    {
        // Delegate the revocation of the strike to the EnforcementWriter service.
        return $this->writer->revokeStrike($strike, $moderator);
    }

    /**
     * Mark a ban as expired and trigger related events and notifications.
     *
     * @param  Ban  $ban  The ban to mark as expired.
     * @return bool Returns true if the ban was successfully marked as expired, false otherwise.
     */
    public function markBanExpired(Ban $ban): bool
    {
        // If the ban is not expired or has already been marked as expired, return false.
        if (! $ban->isExpired() || $ban->expired_notified_at !== null) {
            return false;
        }

        // Mark the ban as expired by updating the 'expired_notified_at' timestamp to the current time.
        $saved = $ban->forceFill(['expired_notified_at' => now()])->save();

        // If the ban was successfully marked as expired, trigger the BanExpired event, log the expiration in the audit log, and send a notification about the ban expiration.
        if ($saved) {
            event(new BanExpired($ban));
            $this->audit->log('ban.expired', $ban);
            $this->notifications->banExpired($ban);
        }

        // Return whether the ban was successfully marked as expired.
        return $saved;
    }
}
