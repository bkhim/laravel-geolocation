<?php

namespace Adrianorosa\GeoLocation\Tests\Providers;

use Adrianorosa\GeoLocation\GeoLocation;
use Adrianorosa\GeoLocation\GeoLocationException;
use Adrianorosa\GeoLocation\Tests\TestCase;
use InvalidArgumentException;
use Mockery;

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
        $this->assertInstanceOf(\Adrianorosa\GeoLocation\Providers\IpInfo::class, $ipinfo);

        // Test MaxMind driver
        $maxmindDr = config(['geolocation.drivers.default' => 'maxmind']);
        $maxmind = GeoLocation::driver($maxmindDr);
        $this->assertInstanceOf(\Adrianorosa\GeoLocation\Providers\MaxMind::class, $maxmind);
    }
}
