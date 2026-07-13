<?php

namespace EloquentWorks\Exile\Events;

use EloquentWorks\Exile\Models\Warning;

/**
 * Event triggered when a warning is issued.
 */
final class WarningIssued
{
    public function __construct(public readonly Warning $warning) {}
}
