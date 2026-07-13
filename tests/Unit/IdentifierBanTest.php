<?php

namespace Tests\Unit;

use EloquentWorks\Exile\Enums\BanType;
use EloquentWorks\Exile\Services\ExileManager;
use EloquentWorks\Exile\Support\EnforcementContext;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class IdentifierBanTest extends TestCase
{
    #[Test]
    public function it_matches_an_exact_ip_ban(): void
    {
        $manager = app(ExileManager::class);
        $ban = $manager->banIp('203.0.113.10', reason: 'Automated abuse');

        $resolved = $manager->resolveActiveBan(new EnforcementContext(ipAddress: '203.0.113.10'));

        self::assertNotNull($resolved);
        self::assertTrue($resolved->is($ban));
        self::assertSame(BanType::Ip, $resolved->type);
    }

    #[Test]
    public function it_matches_ipv4_and_ipv6_network_bans(): void
    {
        $manager = app(ExileManager::class);
        $ipv4 = $manager->banNetwork('203.0.113.0/24');
        $ipv6 = $manager->banNetwork('2001:db8::/32');

        self::assertTrue($manager->resolveActiveBan(new EnforcementContext(ipAddress: '203.0.113.99'))?->is($ipv4));
        self::assertTrue($manager->resolveActiveBan(new EnforcementContext(ipAddress: '2001:db8::42'))?->is($ipv6));
        self::assertNull($manager->resolveActiveBan(new EnforcementContext(ipAddress: '198.51.100.1')));
    }

    #[Test]
    public function it_matches_device_bans_without_storing_raw_fingerprints(): void
    {
        $manager = app(ExileManager::class);
        $ban = $manager->banDevice('device-secret-123');

        self::assertNull($ban->getAttribute('device_fingerprint'));
        self::assertNotSame('device-secret-123', $ban->device_hash);
        self::assertTrue($manager->resolveActiveBan(new EnforcementContext(deviceFingerprint: 'device-secret-123'))?->is($ban));
    }

    #[Test]
    public function it_registers_and_updates_a_device_fingerprint(): void
    {
        $user = $this->user();
        $manager = app(ExileManager::class);

        $first = $manager->registerDevice($user, 'browser-device', '203.0.113.5', 'Laptop');
        $second = $manager->registerDevice($user, 'browser-device', '203.0.113.6', 'Work Laptop');

        self::assertTrue($first->is($second));
        self::assertSame('Work Laptop', $second->label);
        self::assertCount(1, $user->deviceFingerprints);
    }

    #[Test]
    public function it_rejects_an_invalid_ip_address(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        app(ExileManager::class)->banIp(
            ipAddress: 'not-an-ip-address',
            reason: 'Invalid IP'
        );
    }

    #[Test]
    public function it_rejects_an_invalid_cidr_range(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        app(ExileManager::class)->banNetwork(
            cidr: '203.0.113.0/999',
            reason: 'Invalid CIDR'
        );
    }
}
