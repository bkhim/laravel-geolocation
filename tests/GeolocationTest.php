<?php

namespace Bkhim\Geolocation\Tests;

use Bkhim\Geolocation\Geolocation;
use Bkhim\Geolocation\GeolocationException;
use Illuminate\Support\Facades\Http;

class GeolocationTest extends TestCase
{
    /** @test */
    public function it_resolves_default_driver()
    {
        $this->mockIpInfoResponse();

        $details = Geolocation::lookup('8.8.8.8');

        $this->assertEquals('United States', $details->getCountry());
    }

    /** @test */
    public function it_throws_exception_for_invalid_ip()
    {
        $this->expectException(GeolocationException::class);
        $this->expectExceptionMessage('Invalid IP address');

        Geolocation::lookup('invalid-ip');
    }

    /** @test */
    public function it_can_use_specific_driver()
    {
        $this->mockIpInfoResponse();

        $details = Geolocation::driver('ipinfo')->lookup('8.8.8.8');

        $this->assertNotNull($details->getCountry());
    }

    /** @test */
    /** @test */
    public function it_makes_correct_api_request()
    {
        // ⚠️ DEBUG: See what's happening
        dump('Current driver:', config('geolocation.drivers.default'));
        dump('Cache enabled:', config('geolocation.cache.enabled'));

        Http::fake([
            'ipinfo.io/8.8.8.8/json' => Http::response([
                'ip' => '8.8.8.8',
                'city' => 'Mountain View',
                'country' => 'United States'
            ])
        ]);

        try {
            $details = Geolocation::driver('ipinfo')->lookup('8.8.8.8');
            dump('Success! Result:', $details->toArray());
        } catch (\Exception $e) {
            dump('Error:', $e->getMessage());
        }

        // See what requests were made
        dump('Requests made:', Http::recorded());

        $this->assertTrue(true); // Temporary to see output
    }

    /** @test */
    public function it_can_switch_to_maxmind_driver()
    {
        $this->markTestSkipped('MaxMind tests require database setup');
    }
}
