<?php

namespace Bkhim\Geolocation\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Bkhim\Geolocation\GeolocationServiceProvider;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [GeolocationServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup default config for testing
        $app['config']->set('geolocation.drivers.default', 'ipinfo');
        $app['config']->set('geolocation.providers.maxmind.database_path', storage_path('GeoLite2-City.mmdb'));
        $app['config']->set('geolocation.cache.enabled', false);
        $app['config']->set('cache.default', 'array');

        $app['config']->set('geolocation.providers.maxmind.database_path',
            storage_path('app/geoip/test.mmdb')
        );

        // Create the test file if it doesn't exist
        $testDbPath = storage_path('app/geoip/test.mmdb');
        if (!file_exists($testDbPath)) {
            if (!file_exists(dirname($testDbPath))) {
                mkdir(dirname($testDbPath), 0755, true);
            }
            file_put_contents($testDbPath, 'TEST_MAXMIND_DATABASE');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        Http::fake();

        app('cache')->flush();
    }

    protected function mockIpInfoResponse($ip = '8.8.8.8', $data = null)
    {
        $response = $data ?? [
            'ip' => $ip,
            'city' => 'Mountain View',
            'region' => 'California',
            'country' => 'United States',
            'loc' => '37.3860,-122.0838',
            'org' => 'AS15169 Google LLC',
            'timezone' => 'America/Los_Angeles'
        ];

        Http::fake([
            "ipinfo.io/{$ip}/json" => Http::response($response)
        ]);
    }

    protected function createTestMaxMindDatabase()
    {
        $databasePath = storage_path('app/geoip/test.mmdb');

        // Create a minimal test database if it doesn't exist
        if (!file_exists($databasePath)) {
            file_put_contents($databasePath, 'MAXMIND_TEST_DATABASE');
        }

        return $databasePath;
    }

    protected function mockMaxMindReader($ip = '8.8.8.8', $data = null)
    {
        $mockReader = $this->mock(\GeoIp2\Database\Reader::class);

        $recordData = $data ?? [
            'city' => ['name' => 'Mountain View'],
            'mostSpecificSubdivision' => ['name' => 'California'],
            'country' => ['name' => 'United States', 'isoCode' => 'US'],
            'location' => ['latitude' => 37.3860, 'longitude' => -122.0838]
        ];

        $mockRecord = new \GeoIp2\Model\City($recordData);

        $mockReader->shouldReceive('city')
            ->with($ip)
            ->andReturn($mockRecord);

        return $mockReader;
    }
}
