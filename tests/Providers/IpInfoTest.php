<?php

namespace Bkhim\Geolocation\Tests\Providers;

use Bkhim\Geolocation\Geolocation;
use Bkhim\Geolocation\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class IpInfoTest extends TestCase
{
    /** @test */
    public function it_makes_correct_api_request()
    {
        Http::fake([
            'ipinfo.io/8.8.8.8/json' => Http::response([
                'ip' => '8.8.8.8',
                'city' => 'Mountain View',
                'country' => 'United States'
            ])
        ]);

        $details = Geolocation::driver('ipinfo')->lookup('8.8.8.8');

        $this->assertEquals('United States', $details->getCountry());

        Http::assertSent(function ($request) {
            return $request->url() === 'https://ipinfo.io/8.8.8.8/json' &&
                $request->hasHeader('Authorization');
        });
    }
}
