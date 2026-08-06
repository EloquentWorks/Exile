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
        // Ban an exact IP address and verify that it can be resolved correctly
        $manager = app(ExileManager::class);
        $ban = $manager->banIp('203.0.113.10', reason: 'Automated abuse');

        // Verify that the ban can be resolved using the exact IP address
        $resolved = $manager->resolveActiveBan(new EnforcementContext(ipAddress: '203.0.113.10'));

        // Assert that the resolved ban is not null and matches the original ban
        self::assertNotNull($resolved);
        self::assertTrue($resolved->is($ban));
        self::assertSame(BanType::Ip, $resolved->type);
    }

    #[Test]
    public function it_matches_ipv4_and_ipv6_network_bans(): void
    {
        // Ban a network range for both IPv4 and IPv6 and verify that IP addresses within those ranges are correctly matched
        $manager = app(ExileManager::class);
        $ipv4 = $manager->banNetwork('203.0.113.0/24');
        $ipv6 = $manager->banNetwork('2001:db8::/32');

        // Verify that the network bans match IP addresses within their respective ranges
        self::assertTrue($manager->resolveActiveBan(new EnforcementContext(ipAddress: '203.0.113.99'))?->is($ipv4));
        self::assertTrue($manager->resolveActiveBan(new EnforcementContext(ipAddress: '2001:db8::42'))?->is($ipv6));
        self::assertNull($manager->resolveActiveBan(new EnforcementContext(ipAddress: '198.51.100.1')));
    }

    #[Test]
    public function it_matches_device_bans_without_storing_raw_fingerprints(): void
    {
        // Ban a device fingerprint and verify that it can be resolved without storing the raw fingerprint in the database
        $manager = app(ExileManager::class);
        $ban = $manager->banDevice('device-secret-123');

        // Verify that the raw device fingerprint is not stored in the database for security reasons
        self::assertNull($ban->getAttribute('device_fingerprint'));
        self::assertNotSame('device-secret-123', $ban->device_hash);
        self::assertTrue($manager->resolveActiveBan(new EnforcementContext(deviceFingerprint: 'device-secret-123'))?->is($ban));
    }

    #[Test]
    public function it_registers_and_updates_a_device_fingerprint(): void
    {
        // Register a device fingerprint for the user and verify that it is stored correctly
        $user = $this->user();
        $manager = app(ExileManager::class);

        // Register the same device fingerprint twice with different labels and IP addresses
        $first = $manager->registerDevice($user, 'browser-device', '203.0.113.5', 'Laptop');
        $second = $manager->registerDevice($user, 'browser-device', '203.0.113.6', 'Work Laptop');

        // Assert that the first and second device registrations are considered the same device
        self::assertTrue($first->is($second));
        self::assertSame('Work Laptop', $second->label);
        self::assertCount(1, $user->deviceFingerprints);
    }

    #[Test]
    public function it_rejects_an_invalid_ip_address(): void
    {
        // Expect an InvalidArgumentException to be thrown when attempting to ban an invalid IP address
        $this->expectException(
            InvalidArgumentException::class
        );

        // Attempt to ban an invalid IP address, which should trigger the exception
        app(ExileManager::class)->banIp(
            ipAddress: 'not-an-ip-address',
            reason: 'Invalid IP'
        );
    }

    #[Test]
    public function it_rejects_an_invalid_cidr_range(): void
    {
        // Expect an InvalidArgumentException to be thrown when attempting to ban an invalid CIDR range
        $this->expectException(
            InvalidArgumentException::class
        );

        // Attempt to ban an invalid CIDR range, which should trigger the exception
        app(ExileManager::class)->banNetwork(
            cidr: '203.0.113.0/999',
            reason: 'Invalid CIDR'
        );
    }
}
