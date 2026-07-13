<?php

namespace EloquentWorks\Exile\Events;

use EloquentWorks\Exile\Models\Ban;

/**
 * Event triggered when a ban expires.
 */
final class BanExpired
{
    public function __construct(public readonly Ban $ban) {}
}
