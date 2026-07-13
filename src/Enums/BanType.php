<?php

namespace EloquentWorks\Exile\Enums;

/**
 * Enum representing the type of a ban.
 */
enum BanType: string
{
    case Account = 'account';
    case Ip = 'ip';
    case AccountAndIp = 'account_and_ip';
    case Network = 'network';
    case Device = 'device';
    case AccountDeviceAndIp = 'account_device_and_ip';
}
