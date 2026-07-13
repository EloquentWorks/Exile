<?php

namespace EloquentWorks\Exile\Events;

use EloquentWorks\Exile\Models\Restriction;

/**
 * Event triggered when a restriction is revoked.
 */
final class RestrictionRevoked
{
    public function __construct(public readonly Restriction $restriction) {}
}
