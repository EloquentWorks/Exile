<?php

namespace EloquentWorks\Exile\Events;

use EloquentWorks\Exile\Models\Restriction;

/**
 * Event triggered when a restriction is issued.
 */
final class RestrictionIssued
{
    public function __construct(public readonly Restriction $restriction) {}
}
