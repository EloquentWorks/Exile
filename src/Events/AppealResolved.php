<?php

namespace EloquentWorks\Exile\Events;

use EloquentWorks\Exile\Models\BanAppeal;

final class AppealResolved
{
    /**
     * Create a new event instance.
     *
     * @param  BanAppeal  $appeal  The resolved ban appeal.
     * @return void
     */
    public function __construct(public readonly BanAppeal $appeal) {}
}
