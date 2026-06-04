<?php

use Bkhim\Geolocation\GeolocationDetails;
use Bkhim\Geolocation\GeolocationException;
use Bkhim\Geolocation\Providers\IpGeolocation;
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
            'state_prov' => 'California',
            'country_code2' => 'US',
            'latitude' => '37.386',
            'longitude' => '-122.084',
            'continent_name' => 'North America',
            'continent_code' => 'NA',
            'zipcode' => '94043',
            'time_zone' => ['name' => 'America/Los_Angeles', 'offset' => -8],
            'currency' => ['name' => 'US Dollar', 'code' => 'USD', 'symbol' => '$'],
            'isp' => 'Google LLC',
        ]),
    ]);

    $cache = Cache::store();
    $provider = new IpGeolocation($cache);

    $details = $provider->lookup('8.8.8.8');

    expect($details)->toBeInstanceOf(GeolocationDetails::class);
    expect($details->getIp())->toBe('8.8.8.8');
    expect($details->getCity())->toBe('Mountain View');
});

it('throws exception when api key is missing', function () {
    config(['geolocation.providers.ipgeolocation.api_key' => null]);

    $cache = Cache::store();
    $provider = new IpGeolocation($cache);

    expect(fn () => $provider->lookup('8.8.8.8'))
        ->toThrow(GeolocationException::class, 'API key is missing');
});

it('throws exception for invalid ip address', function () {
    $cache = Cache::store();
    $provider = new IpGeolocation($cache);

    expect(fn () => $provider->lookup('invalid-ip'))
        ->toThrow(GeolocationException::class);
});

it('throws exception for missing ip in response', function () {
    Http::fake([
        '*' => Http::response([
            'city' => 'Mountain View',
            'country_code2' => 'US',
        ]),
    ]);

    $cache = Cache::store();
    $provider = new IpGeolocation($cache);

    expect(fn () => $provider->lookup('8.8.8.8'))
        ->toThrow(GeolocationException::class, 'Incomplete');
});

it('handles http error responses', function () {
    Http::fake([
        '*' => Http::response([], 401),
    ]);

    $cache = Cache::store();
    $provider = new IpGeolocation($cache);

    expect(fn () => $provider->lookup('8.8.8.8'))
        ->toThrow(GeolocationException::class);
});

it('includes optional security fields when available', function () {
    Http::fake([
        '*' => Http::response([
            'ip' => '8.8.8.8',
            'city' => 'Mountain View',
            'country_code2' => 'US',
            'latitude' => '37.386',
            'longitude' => '-122.084',
            'time_zone' => ['name' => 'America/Los_Angeles'],
            'currency' => ['name' => 'US Dollar', 'code' => 'USD', 'symbol' => '$'],
            'security' => [
                'is_proxy' => false,
                'is_crawler' => false,
                'is_tor' => false,
            ],
            'device' => [
                'is_mobile' => false,
            ],
        ]),
    ]);

    $cache = Cache::store();
    $provider = new IpGeolocation($cache);

    $details = $provider->lookup('8.8.8.8');

    expect($details->isProxy())->toBe(false);
    expect($details->isCrawler())->toBe(false);
    expect($details->isTor())->toBe(false);
    expect($details->isMobile())->toBe(false);
});

it('transforms timezone offset correctly', function () {
    Http::fake([
        '*' => Http::response([
            'ip' => '8.8.8.8',
            'city' => 'Mountain View',
            'country_code2' => 'US',
            'latitude' => '37.386',
            'longitude' => '-122.084',
            'time_zone' => ['name' => 'America/Los_Angeles', 'offset' => -8],
            'currency' => ['name' => 'US Dollar', 'code' => 'USD', 'symbol' => '$'],
        ]),
    ]);

    $cache = Cache::store();
    $provider = new IpGeolocation($cache);

    $details = $provider->lookup('8.8.8.8');

    expect($details->getTimezoneOffset())->toEqual(-8);
});

it('includes hostname when available', function () {
    Http::fake([
        '*' => Http::response([
            'ip' => '8.8.8.8',
            'hostname' => 'dns.google',
            'city' => 'Mountain View',
            'country_code2' => 'US',
            'latitude' => '37.386',
            'longitude' => '-122.084',
            'time_zone' => ['name' => 'America/Los_Angeles'],
            'currency' => ['name' => 'US Dollar', 'code' => 'USD', 'symbol' => '$'],
        ]),
    ]);

    $cache = Cache::store();
    $provider = new IpGeolocation($cache);

    $details = $provider->lookup('8.8.8.8');

    expect($details->getHostname())->toBe('dns.google');
});
