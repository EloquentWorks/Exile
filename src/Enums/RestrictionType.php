<?php

namespace EloquentWorks\Exile\Enums;

/**
 * Enum representing the type of a restriction.
 */
enum RestrictionType: string
{
    case Login = 'login';
    case Posting = 'posting';
    case ReadOnly = 'read_only';
    case Shadow = 'shadow';
}
