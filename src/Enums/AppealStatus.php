<?php

namespace EloquentWorks\Exile\Enums;

enum AppealStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Denied = 'denied';
    case Withdrawn = 'withdrawn';
}
