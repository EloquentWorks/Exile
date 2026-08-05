<?php

namespace EloquentWorks\Exile\Events;

use EloquentWorks\Exile\Models\Ban;

final class BanRevoked
{
    /**
     * Create a new event instance.
     *
     * @param  Ban  $ban  The revoked ban.
     * @return void
     */
    public function __construct(public readonly Ban $ban) {}
}
