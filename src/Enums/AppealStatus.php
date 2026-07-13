<?php

namespace EloquentWorks\Exile\Enums;

/**
 * Enum representing the status of an appeal.
 */
enum AppealStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Denied = 'denied';
    case Withdrawn = 'withdrawn';
}
