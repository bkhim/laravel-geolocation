<?php

use Bkhim\Geolocation\GeolocationDetails;
use Bkhim\Geolocation\GeolocationException;
use Bkhim\Geolocation\Providers\Ip2Locationio;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
});

it('can lookup geolocation data for an ip address', function () {
    Http::fake([
        '*' => Http::response([
            "ip" => '8.8.8.8',
            "country_code" => "US",
            "country_name" => "United States of America",
            "region_name" => "California",
            "city_name" => "Mountain View",
            "latitude" => 37.38605,
            "longitude" => -122.08385,
            "zip_code" => "94035",
            "time_zone" => "-07:00",
            "asn" => "15169",
            "as" => "Google LLC",
            "is_proxy" => false,
        ]),
    ]);

    $cache = Cache::store();
    $provider = new Ip2Locationio($cache);

    $details = $provider->lookup('8.8.8.8');

    expect($details)->toBeInstanceOf(GeolocationDetails::class);
    expect($details->getIp())->toBe('8.8.8.8');
    expect($details->getCity())->toBe('Mountain View');
});

it('throws exception for invalid ip address', function () {
    $cache = Cache::store();
    $provider = new Ip2Locationio($cache);

    expect(fn () => $provider->lookup('invalid-ip'))
        ->toThrow(GeolocationException::class);
});

it('throws exception for missing ip in response', function () {
    Http::fake([
        '*' => Http::response([
            'country_code' => 'US',
            'city' => 'Mountain View',
        ]),
    ]);

    $cache = Cache::store();
    $provider = new Ip2Locationio($cache);

    expect(fn () => $provider->lookup('8.8.8.8'))
        ->toThrow(GeolocationException::class, 'Incomplete');
});

it('handles http error responses', function () {
    Http::fake([
        '*' => Http::response([], 401),
    ]);

    $cache = Cache::store();
    $provider = new Ip2Locationio($cache);

    expect(fn () => $provider->lookup('8.8.8.8'))
        ->toThrow(GeolocationException::class);
});

it('transforms timezone offset correctly', function () {
    Http::fake([
        '*' => Http::response([
            "ip" => '8.8.8.8',
            "country_code" => "US",
            "country_name" => "United States of America",
            "region_name" => "California",
            "city_name" => "Mountain View",
            "latitude" => 37.38605,
            "longitude" => -122.08385,
            "zip_code" => "94035",
            "time_zone" => "-07:00",
            "asn" => "15169",
            "as" => "Google LLC",
            "is_proxy" => false,
        ]),
    ]);

    $cache = Cache::store();
    $provider = new Ip2Locationio($cache);

    $details = $provider->lookup('8.8.8.8');

    expect($details->getTimezoneOffset())->toEqual(-7);
});