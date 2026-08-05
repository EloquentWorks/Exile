<?php

namespace EloquentWorks\Exile\Events;

use EloquentWorks\Exile\Models\Restriction;

final class RestrictionIssued
{
    /**
     * Create a new event instance.
     *
     * @param  Restriction  $restriction  The issued restriction.
     * @return void
     */
    public function __construct(public readonly Restriction $restriction) {}
}
