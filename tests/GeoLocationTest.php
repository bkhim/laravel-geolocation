<?php

namespace Bkhim\GeoLocation\Tests;

use Bkhim\GeoLocation\GeoLocation;
use Bkhim\GeoLocation\GeoLocationDetails;
use Bkhim\GeoLocation\GeoLocationException;
use Illuminate\Support\Facades\Http;

class GeoLocationTest extends TestCase
{
    /** @test */
    public function it_returns_geolocation_details_for_valid_ip()
    {
        $info = GeoLocation::lookup('8.8.8.8');

        $this->assertInstanceOf(GeoLocationDetails::class, $info);
        $this->assertEquals('8.8.8.8', $info->getIp());
        $this->assertEquals('Mountain View', $info->getCity());
        $this->assertEquals('California', $info->getRegion());
        $this->assertEquals('United States', $info->getCountry());
        $this->assertEquals('US', $info->getCountryCode());
        $this->assertEquals(37.3860, $info->getLatitude());
        $this->assertEquals(-122.0838, $info->getLongitude());
    }

    /** @test */
    public function it_handles_country_name_translations()
    {
        $this->app->setLocale('en');
        $info = GeoLocation::lookup('8.8.8.8');
        $this->assertEquals('United States', $info->getCountry());

        $this->app->setLocale('pt');
        $info = GeoLocation::lookup('8.8.8.8');
        $this->assertEquals('Estados Unidos', $info->getCountry());
    }

    /** @test */
    public function it_throws_exception_for_invalid_ip()
    {
        $this->expectException(GeoLocationException::class);
        $this->expectExceptionMessage('Invalid IP address');

        GeoLocation::lookup('invalid.ip.address');
    }

    /** @test */
    public function it_handles_rate_limit_errors()
    {
        Http::fake([
            'ipinfo.io/8.8.8.8/geo' => Http::response([], 429)
        ]);

        $this->expectException(GeoLocationException::class);
        $this->expectExceptionMessage('Rate limit exceeded');

        GeoLocation::lookup('8.8.8.8');
    }

    /** @test */
    public function it_handles_invalid_api_key_errors()
    {
        Http::fake([
            'ipinfo.io/8.8.8.8/geo' => Http::response([], 401)
        ]);

        $this->expectException(GeoLocationException::class);
        $this->expectExceptionMessage('Invalid API key');

        GeoLocation::lookup('8.8.8.8');
    }

    /** @test */
    public function it_handles_api_timeouts()
    {
        Http::fake([
            'ipinfo.io/8.8.8.8/geo' => function () {
                throw new \GuzzleHttp\Exception\ConnectException(
                    'Connection timed out',
                    new \GuzzleHttp\Psr7\Request('GET', 'test')
                );
            }
        ]);

        $this->expectException(GeoLocationException::class);
        $this->expectExceptionMessage('Connection timeout');

        GeoLocation::lookup('8.8.8.8');
    }
}
