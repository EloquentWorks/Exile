<?php

namespace EloquentWorks\Exile\Enums;

/**
 * Enum representing the severity of a warning.
 */
enum WarningSeverity: string
{
    case Info = 'info';
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Final = 'final';
}
