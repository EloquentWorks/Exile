<?php

namespace EloquentWorks\Exile\Events;

use EloquentWorks\Exile\Models\BanAppeal;

final class AppealSubmitted
{
    /**
     * Create a new event instance.
     *
     * @param  BanAppeal  $appeal  The submitted ban appeal.
     * @return void
     */
    public function __construct(public readonly BanAppeal $appeal) {}
}
