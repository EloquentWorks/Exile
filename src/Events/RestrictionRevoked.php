<?php

namespace EloquentWorks\Exile\Events;

use EloquentWorks\Exile\Models\Restriction;

final class RestrictionRevoked
{
    /**
     * Create a new event instance.
     *
     * @param  Restriction  $restriction  The revoked restriction.
     * @return void
     */
    public function __construct(public readonly Restriction $restriction) {}
}
