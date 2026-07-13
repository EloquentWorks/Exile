<?php

namespace EloquentWorks\Exile\Events;

use EloquentWorks\Exile\Models\Strike;

/**
 * Event triggered when a strike is issued.
 */
final class StrikeIssued
{
    public function __construct(public readonly Strike $strike) {}
}
