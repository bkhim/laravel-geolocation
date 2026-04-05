<?php

use Bkhim\Geolocation\GeolocationDetails;
use Bkhim\Geolocation\GeolocationException;
use Bkhim\Geolocation\Providers\Ip2LocationIo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
});

it('can lookup geolocation data for an ip address', function () {
    Http::fake([
        '*' => Http::response([
            "ip"           => '8.8.8.8',
            "country_code" => "US",
            "country_name" => "United States of America",
            "region_name"  => "California",
            "city_name"    => "Mountain View",
            "latitude"     => 37.38605,
            "longitude"    => -122.08385,
            "zip_code"     => "94035",
            "time_zone"    => "-07:00",
            "asn"          => "15169",
            "as"           => "Google LLC",
            "is_proxy"     => false,
        ]),
    ]);

    $cache    = Cache::store();
    $provider = new Ip2LocationIo($cache);

    $details = $provider->lookup('8.8.8.8');

    expect($details)->toBeInstanceOf(GeolocationDetails::class);
    expect($details->getIp())->toBe('8.8.8.8');
    expect($details->getCity())->toBe('Mountain View');
});

it('throws exception for invalid ip address', function () {
    $cache    = Cache::store();
    $provider = new Ip2LocationIo($cache);

    expect(fn() => $provider->lookup('invalid-ip'))
        ->toThrow(GeolocationException::class);
});

it('throws exception for missing ip in response', function () {
    Http::fake([
        '*' => Http::response([
            'country_code' => 'US',
            'city'         => 'Mountain View',
        ]),
    ]);

    $cache    = Cache::store();
    $provider = new Ip2LocationIo($cache);

    expect(fn() => $provider->lookup('8.8.8.8'))
        ->toThrow(GeolocationException::class, 'Incomplete');
});

it('handles http error responses', function () {
    Http::fake([
        '*' => Http::response([], 401),
    ]);

    $cache    = Cache::store();
    $provider = new Ip2LocationIo($cache);

    expect(fn() => $provider->lookup('8.8.8.8'))
        ->toThrow(GeolocationException::class);
});

it('handles specific http error codes correctly', function () {
    Http::fake([
        '*' => Http::response([], 429),
    ]);

    $cache = Cache::store();
    $provider = new Ip2LocationIo($cache);

    expect(fn () => $provider->lookup('8.8.8.8'))
        ->toThrow(GeolocationException::class, 'Rate limit exceeded');
});

it('validates language parameter', function () {
    // Mock the config to return invalid language
    config(['geolocation.providers.ip2locationio.language' => 'invalid']);

    $cache = Cache::store();
    $provider = new Ip2LocationIo($cache);

    expect(fn () => $provider->lookup('8.8.8.8'))
        ->toThrow(GeolocationException::class, 'Invalid language value');
});

it('formats asn with AS prefix correctly', function () {
    Http::fake([
        '*' => Http::response([
            "ip" => '8.8.8.8',
            "country_code" => "US",
            "city_name" => "Mountain View",
            "latitude" => 37.38605,
            "longitude" => -122.08385,
            "asn" => "15169",
            "as" => "Google LLC",
            "is_proxy" => false,
        ]),
    ]);

    $cache = Cache::store();
    $provider = new Ip2LocationIo($cache);

    $details = $provider->lookup('8.8.8.8');

    expect($details->getAsn())->toBe('AS15169');
});

it('transforms timezone offset correctly', function () {
    Http::fake([
        '*' => Http::response([
            "ip"           => '8.8.8.8',
            "country_code" => "US",
            "country_name" => "United States of America",
            "region_name"  => "California",
            "city_name"    => "Mountain View",
            "latitude"     => 37.38605,
            "longitude"    => -122.08385,
            "zip_code"     => "94035",
            "time_zone"    => "-07:00",
            "asn"          => "15169",
            "as"           => "Google LLC",
            "is_proxy"     => false,
        ]),
    ]);

    $cache    = Cache::store();
    $provider = new Ip2LocationIo($cache);

    $details = $provider->lookup('8.8.8.8');

    expect($details->getTimezoneOffset())->toEqual(-7);
});

it('can fetch real data from ip2location.io api', function () {
    // Test without API key (free tier - 1000 requests/day)
    config(['geolocation.providers.ip2locationio.api_key' => null]);

    $cache = Cache::store();
    $provider = new Ip2LocationIo($cache);

    $details = $provider->lookup('8.8.8.8');

    expect($details)->toBeInstanceOf(GeolocationDetails::class);
    expect($details->getIp())->toBe('8.8.8.8');
    expect($details->getCountry())->toBe('United States of America');
    expect($details->getCountryCode())->toBe('US');
    expect($details->getCity())->toBe('Mountain View');
    expect($details->getRegion())->toBe('California');
    expect($details->getAsn())->toBe('AS15169');
    expect($details->getAsnName())->toBe('Google LLC');
    expect($details->getTimezoneOffset())->toEqual(-7.0); // Use toEqual for type flexibility
    expect($details->isProxy())->toBe(false);
});

it('handles translation error gracefully and retries without language', function () {
    // First response: translation error
    // Second response: success without lang parameter
    Http::fake([
        '*' => Http::sequence()
            ->push([
                'error' => [
                    'error_code' => 10004,
                    'error_message' => 'Translation is not available with your plan'
                ]
            ], 200)
            ->push([
                "ip" => '8.8.8.8',
                "country_code" => "US",
                "country_name" => "United States of America",
                "region_name" => "California",
                "city_name" => "Mountain View",
                "latitude" => 37.38605,
                "longitude" => -122.08385,
                "asn" => "15169",
                "as" => "Google LLC",
                "is_proxy" => false,
            ], 200),
    ]);

    // Configure with non-English language and API key to trigger lang parameter
    config([
        'geolocation.providers.ip2locationio.api_key' => 'test_key',
        'geolocation.providers.ip2locationio.language' => 'es'
    ]);

    $cache = Cache::store();
    $provider = new Ip2LocationIo($cache);

    $details = $provider->lookup('8.8.8.8');

    expect($details)->toBeInstanceOf(GeolocationDetails::class);
    expect($details->getIp())->toBe('8.8.8.8');
    expect($details->getCountry())->toBe('United States of America');
});

it('does not include lang parameter for free tier without api key', function () {
    Http::fake([
        '*' => Http::response([
            "ip" => '8.8.8.8',
            "country_code" => "US",
            "country_name" => "United States of America",
            "region_name" => "California",
            "city_name" => "Mountain View",
            "latitude" => 37.38605,
            "longitude" => -122.08385,
            "asn" => "15169",
            "as" => "Google LLC",
            "is_proxy" => false,
        ]),
    ]);

    // Configure without API key but with language
    config([
        'geolocation.providers.ip2locationio.api_key' => null,
        'geolocation.providers.ip2locationio.language' => 'es'
    ]);

    $cache = Cache::store();
    $provider = new Ip2LocationIo($cache);

    $details = $provider->lookup('8.8.8.8');

    expect($details)->toBeInstanceOf(GeolocationDetails::class);
    expect($details->getIp())->toBe('8.8.8.8');

    // Verify the request was made without lang parameter
    Http::assertSent(function ($request) {
        $url = $request->url();
        return strpos($url, 'lang=') === false; // Should not contain lang parameter
    });
});

it('does not include lang parameter when language is en', function () {
    Http::fake([
        '*' => Http::response([
            "ip" => '8.8.8.8',
            "country_code" => "US",
            "country_name" => "United States of America",
            "region_name" => "California",
            "city_name" => "Mountain View",
            "latitude" => 37.38605,
            "longitude" => -122.08385,
            "asn" => "15169",
            "as" => "Google LLC",
            "is_proxy" => false,
        ]),
    ]);

    // Configure with API key but English language
    config([
        'geolocation.providers.ip2locationio.api_key' => 'test_key',
        'geolocation.providers.ip2locationio.language' => 'en'
    ]);

    $cache = Cache::store();
    $provider = new Ip2LocationIo($cache);

    $details = $provider->lookup('8.8.8.8');

    expect($details)->toBeInstanceOf(GeolocationDetails::class);

    // Verify the request was made without lang parameter (since 'en' is default)
    Http::assertSent(function ($request) {
        $url = $request->url();
        return strpos($url, 'lang=') === false; // Should not contain lang parameter
    });
});

it('would throw translation error if automatic retry did not exist', function () {
    // This test simulates what WOULD happen without the automatic retry fix
    // by temporarily disabling the retry mechanism

    Http::fake([
        '*' => Http::response([
            'error' => [
                'error_code' => 10004,
                'error_message' => 'Translation is not available with your plan.'
            ]
        ], 200),
    ]);

    // Create a mock provider that skips the retry (simulate old behavior)
    $cache = Cache::store();
    $provider = new class($cache) extends Ip2LocationIo {
        protected function fetchGeolocationData($ipAddress, $skipLanguage = false): array {
            // Force skip retry logic to demonstrate the error
            return parent::fetchGeolocationData($ipAddress, true); // Always skip retry
        }
    };

    // Configure with API key and non-English language to trigger the error
    config([
        'geolocation.providers.ip2locationio.api_key' => 'free_plan_key',
        'geolocation.providers.ip2locationio.language' => 'es'
    ]);

    // This should throw the translation error since retry is disabled
    expect(fn () => $provider->lookup('8.8.8.8'))
        ->toThrow(GeolocationException::class, 'IP2Location.io API error: Translation is not available with your plan.');
});
