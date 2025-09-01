<?php

namespace Adrianorosa\GeoLocation\Tests\Providers;

use Adrianorosa\GeoLocation\Providers\IpInfo;
use Adrianorosa\GeoLocation\GeoLocationException;
use Adrianorosa\GeoLocation\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class IpInfoTest extends TestCase
{
    /** @test */
    public function it_uses_cache_to_prevent_redundant_api_calls()
    {
        // Clear any previous calls
        Http::fake();

        $ipInfo = new IpInfo(app('http'), app('cache'));

        // First call - should make API request
        $result1 = $ipInfo->lookup('8.8.8.8');

        // Second call - should use cache
        $result2 = $ipInfo->lookup('8.8.8.8');

        // Assert only one API call was made
        Http::assertSentCount(1);
        $this->assertEquals($result1->getIp(), $result2->getIp());
    }

    /** @test */
    public function it_validates_ip_before_api_call()
    {
        $this->expectException(GeoLocationException::class);
        $this->expectExceptionMessage('Invalid IP address: invalid.ip');

        $ipInfo = new IpInfo(app('http'), app('cache'));
        $ipInfo->lookup('invalid.ip');
    }

    /** @test */
    public function it_throws_exception_for_missing_api_key()
    {
        // Temporarily remove API key from config
        config(['geolocation.providers.ipinfo.access_token' => null]);

        $this->expectException(GeoLocationException::class);
        $this->expectExceptionMessage('IpInfo API key is missing');

        $ipInfo = new IpInfo(app('http'), app('cache'));
        $ipInfo->lookup('8.8.8.8');
    }
}
