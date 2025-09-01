<?php

namespace Bkhim\GeoLocation\Tests\Providers;

use Bkhim\GeoLocation\GeoLocation;
use Bkhim\GeoLocation\Tests\TestCase;
use InvalidArgumentException;

class MaxMind extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Switch to maxmind driver for these tests
        config(['geolocation.drivers.default' => 'maxmind']);
    }

    /** @test */
    public function it_throws_exception_for_missing_database()
    {
        // Configure invalid database path
        config(['geolocation.providers.maxmind.database_path' => '/invalid/path.mmdb']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('MaxMind database path is invalid');

        GeoLocation::lookup('8.8.8.8');
    }

    /** @test */
    public function it_can_switch_between_drivers()
    {
        // Test IpInfo driver
        $ipinfoDr= config(['geolocation.drivers.default' => 'ipinfo']);
        $ipinfo = GeoLocation::driver($ipinfoDr);
        $this->assertInstanceOf(\Bkhim\GeoLocation\Providers\IpInfo::class, $ipinfo);

        // Test MaxMind driver
        $maxmindDr = config(['geolocation.drivers.default' => 'maxmind']);
        $maxmind = GeoLocation::driver($maxmindDr);
        $this->assertInstanceOf(\Bkhim\GeoLocation\Providers\MaxMind::class, $maxmind);
    }
}
