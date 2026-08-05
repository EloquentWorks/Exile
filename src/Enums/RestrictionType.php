<?php

namespace EloquentWorks\Exile\Enums;

enum RestrictionType: string
{
    case Login = 'login';
    case Posting = 'posting';
    case ReadOnly = 'read_only';
    case Shadow = 'shadow';
}
