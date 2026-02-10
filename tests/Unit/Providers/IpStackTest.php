<?php

use Bkhim\Geolocation\GeolocationDetails;
use Bkhim\Geolocation\GeolocationException;
use Bkhim\Geolocation\Providers\IpStack;
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
            'region_name' => 'California',
            'country_code' => 'US',
            'latitude' => 37.386,
            'longitude' => -122.084,
            'time_zone' => ['id' => 'America/Los_Angeles', 'gmt_offset' => -28800],
            'currency' => ['name' => 'US Dollar', 'code' => 'USD', 'symbol' => '$'],
            'continent_name' => 'North America',
            'continent_code' => 'NA',
            'zip' => '94043',
            'connection' => ['isp' => 'Google LLC', 'asn' => 15169, 'asn_org' => 'GOOGLE'],
            'security' => ['is_proxy' => false, 'is_crawler' => false, 'is_tor' => false],
        ]),
    ]);

    $cache = Cache::driver();
    $provider = new IpStack($cache);

    $details = $provider->lookup('8.8.8.8');

    expect($details)->toBeInstanceOf(GeolocationDetails::class)
        ->and($details->getIp())->toBe('8.8.8.8')
        ->and($details->getCity())->toBe('Mountain View')
        ->and($details->getCountryCode())->toBe('US');
});

it('throws exception for invalid ip address', function () {
    $cache = Cache::driver();
    $provider = new IpStack($cache);

    expect(fn () => $provider->lookup('invalid-ip'))
        ->toThrow(GeolocationException::class);
});

it('throws exception when api key is missing', function () {
    config(['geolocation.providers.ipstack.access_key' => null]);

    $cache = Cache::driver();
    $provider = new IpStack($cache);

    expect(fn () => $provider->lookup('8.8.8.8'))
        ->toThrow(GeolocationException::class, 'API key is missing');
});

it('handles api error responses', function () {
    Http::fake([
        '*' => Http::response([
            'error' => [
                'code' => 106,
                'type' => 'invalid_access_key',
                'info' => 'You have not supplied a valid API Key.',
            ],
        ]),
    ]);

    $cache = Cache::driver();
    $provider = new IpStack($cache);

    expect(fn () => $provider->lookup('8.8.8.8'))
        ->toThrow(GeolocationException::class, 'API error');
});

it('transforms currency data correctly', function () {
    Http::fake([
        '*' => Http::response([
            'ip' => '8.8.8.8',
            'country_code' => 'US',
            'latitude' => 37.386,
            'longitude' => -122.084,
            'currency' => ['name' => 'US Dollar', 'code' => 'USD', 'symbol' => '$'],
        ]),
    ]);

    $cache = Cache::driver();
    $provider = new IpStack($cache);

    $details = $provider->lookup('8.8.8.8');

    expect($details->getCurrency())->toBe('US Dollar')
        ->and($details->getCurrencyCode())->toBe('USD')
        ->and($details->getCurrencySymbol())->toBe('$');
});

it('handles timezone offset calculation', function () {
    Http::fake([
        '*' => Http::response([
            'ip' => '8.8.8.8',
            'country_code' => 'US',
            'latitude' => 37.386,
            'longitude' => -122.084,
            'time_zone' => ['id' => 'America/Los_Angeles', 'gmt_offset' => -28800],
        ]),
    ]);

    $cache = Cache::driver();
    $provider = new IpStack($cache);

    $details = $provider->lookup('8.8.8.8');

    expect($details->getTimezoneOffset())->toEqual(-8);
});
