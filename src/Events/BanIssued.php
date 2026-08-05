<?php

namespace EloquentWorks\Exile\Events;

use EloquentWorks\Exile\Models\Ban;

final class BanIssued
{
    /**
     * Create a new event instance.
     *
     * @param  Ban  $ban  The issued ban.
     * @return void
     */
    public function __construct(public readonly Ban $ban) {}
}
