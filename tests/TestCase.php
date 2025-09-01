<?php

namespace Bkhim\GeoLocation\Tests;

use Bkhim\GeoLocation\GeoLocationServiceProvider;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [GeoLocationServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup default config for testing
        $app['config']->set('geolocation.providers.ipinfo.access_token', 'test-api-key');
        $app['config']->set('geolocation.cache.ttl', 3600);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Prevent ANY real HTTP calls
        Http::preventStrayRequests();

        // Mock successful API response by default
        Http::fake([
            'ipinfo.io/*' => Http::response([
                'ip' => '8.8.8.8',
                'city' => 'Mountain View',
                'region' => 'California',
                'country' => 'US',
                'loc' => '37.3860,-122.0838'
            ])
        ]);
    }
}
