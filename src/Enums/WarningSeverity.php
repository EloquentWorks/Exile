<?php

namespace EloquentWorks\Exile\Enums;

enum WarningSeverity: string
{
    case Info = 'info';
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Final = 'final';
}
