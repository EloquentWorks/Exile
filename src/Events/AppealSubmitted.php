<?php

namespace EloquentWorks\Exile\Events;

use EloquentWorks\Exile\Models\BanAppeal;

/**
 * Event triggered when a ban appeal is submitted.
 */
final class AppealSubmitted
{
    public function __construct(public readonly BanAppeal $appeal) {}
}
