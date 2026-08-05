<?php

namespace EloquentWorks\Exile\Facades;

use EloquentWorks\Exile\Services\ExileManager;
use Illuminate\Support\Facades\Facade;

/** @see ExileManager */
final class Exile extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        // Get the Facade accessor for the ExileManager service.
        return ExileManager::class;
    }
}
