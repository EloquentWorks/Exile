<?php

namespace EloquentWorks\Exile\Events;

use EloquentWorks\Exile\Models\Ban;

/**
 * Event triggered when a ban is revoked.
 */
final class BanRevoked
{
    public function __construct(public readonly Ban $ban) {}
}
