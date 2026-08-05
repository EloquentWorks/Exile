<?php

namespace EloquentWorks\Exile\Events;

use EloquentWorks\Exile\Models\Warning;

final class WarningIssued
{
    /**
     * Create a new event instance.
     *
     * @param  Warning  $warning  The issued warning.
     * @return void
     */
    public function __construct(public readonly Warning $warning) {}
}
