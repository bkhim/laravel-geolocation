<?php

use Bkhim\Geolocation\GeolocationDetails;
use Bkhim\Geolocation\GeolocationException;
use Bkhim\Geolocation\Providers\IpInfo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
});

it('can lookup geolocation data for an ip address', function () {
    Http::fake([
        '*' => Http::response([
            'ip' => '8.8.8.8',
            'city' => 'Mountain View',
            'region' => 'California',
            'country_code' => 'US',
            'loc' => '37.386,-122.084',
            'timezone' => 'America/Los_Angeles',
            'postal' => '94043',
            'org' => 'AS15169 Google LLC',
            'continent' => 'North America',
            'continent_code' => 'NA',
        ]),
    ]);

    $cache = Cache::driver();
    $provider = new IpInfo($cache);

    $details = $provider->lookup('8.8.8.8');

    expect($details)->toBeInstanceOf(GeolocationDetails::class)
        ->and($details->getIp())->toBe('8.8.8.8')
        ->and($details->getCity())->toBe('Mountain View')
        ->and($details->getCountryCode())->toBe('US')
        ->and($details->getLatitude())->toBe(37.386)
        ->and($details->getLongitude())->toBe(-122.084);
});

it('throws exception for invalid ip address', function () {
    $cache = Cache::driver();
    $provider = new IpInfo($cache);

    expect(fn () => $provider->lookup('invalid-ip'))
        ->toThrow(GeolocationException::class);
});

it('throws exception when api key is missing', function () {
    config(['geolocation.providers.ipinfo.access_token' => null]);

    $cache = Cache::driver();
    $provider = new IpInfo($cache);

    expect(fn () => $provider->lookup('8.8.8.8'))
        ->toThrow(GeolocationException::class, 'API key is missing');
});

it('throws exception for incomplete response data', function () {
    Http::fake([
        '*' => Http::response([
            'ip' => '8.8.8.8',
            // Missing required country_code field
        ]),
    ]);

    $cache = Cache::driver();
    $provider = new IpInfo($cache);

    expect(fn () => $provider->lookup('8.8.8.8'))
        ->toThrow(GeolocationException::class, 'Incomplete geolocation data');
});

it('handles http error responses', function () {
    Http::fake([
        '*' => Http::response([], 401),
    ]);

    $cache = Cache::driver();
    $provider = new IpInfo($cache);

    expect(fn () => $provider->lookup('8.8.8.8'))
        ->toThrow(GeolocationException::class, 'Invalid API key');
});

it('handles rate limit errors', function () {
    Http::fake([
        '*' => Http::response([], 429),
    ]);

    $cache = Cache::driver();
    $provider = new IpInfo($cache);

    expect(fn () => $provider->lookup('8.8.8.8'))
        ->toThrow(GeolocationException::class, 'Rate limit exceeded');
});

it('parses asn information from org field', function () {
    Http::fake([
        '*' => Http::response([
            'ip' => '8.8.8.8',
            'city' => 'Mountain View',
            'region' => 'California',
            'country_code' => 'US',
            'loc' => '37.386,-122.084',
            'org' => 'AS15169 Google LLC',
        ]),
    ]);

    $cache = Cache::driver();
    $provider = new IpInfo($cache);

    $details = $provider->lookup('8.8.8.8');

    expect($details->getAsn())->toBe('AS15169')
        ->and($details->getAsnName())->toBe('Google LLC');
});

it('calculates timezone offset correctly', function () {
    Http::fake([
        '*' => Http::response([
            'ip' => '8.8.8.8',
            'city' => 'Mountain View',
            'country_code' => 'US',
            'loc' => '37.386,-122.084',
            'timezone' => 'America/Los_Angeles',
        ]),
    ]);

    $cache = Cache::driver();
    $provider = new IpInfo($cache);

    $details = $provider->lookup('8.8.8.8');

    expect($details->getTimezone())->toBe('America/Los_Angeles')
        ->and($details->getTimezoneOffset())->toBeNumeric();
});
