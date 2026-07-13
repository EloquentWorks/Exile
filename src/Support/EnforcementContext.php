<?php

namespace EloquentWorks\Exile\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents the context in which an enforcement action is being taken.
 */
final readonly class EnforcementContext
{
    /**
     * Constructs a new EnforcementContext instance.
     *
     * @param  Model|null  $account  The account associated with the enforcement action.
     * @param  string|null  $ipAddress  The IP address associated with the enforcement action.
     * @param  string|null  $deviceFingerprint  The device fingerprint associated with the enforcement action.
     */
    public function __construct(
        public ?Model $account = null,
        public ?string $ipAddress = null,
        public ?string $deviceFingerprint = null,
    ) {}
}
