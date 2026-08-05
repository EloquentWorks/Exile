<?php

namespace EloquentWorks\Exile\Events;

use EloquentWorks\Exile\Models\Strike;

final class StrikeIssued
{
    /**
     * Create a new event instance.
     *
     * @param  Strike  $strike  The issued strike.
     * @return void
     */
    public function __construct(public readonly Strike $strike) {}
}
