<?php

namespace EloquentWorks\Exile\Facades;

use EloquentWorks\Exile\Services\ExileManager;
use Illuminate\Support\Facades\Facade;

/** @see ExileManager */
final class Exile extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ExileManager::class;
    }
}
