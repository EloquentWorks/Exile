<?php

namespace EloquentWorks\Exile\Events;

use EloquentWorks\Exile\Models\Ban;

final class BanExpired
{
    /**
     * Create a new event instance.
     *
     * @param  Ban  $ban  The expired ban.
     * @return void
     */
    public function __construct(public readonly Ban $ban) {}
}
