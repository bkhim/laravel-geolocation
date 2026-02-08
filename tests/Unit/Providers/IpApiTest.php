 <?php

use Bkhim\Geolocation\GeolocationDetails;
use Bkhim\Geolocation\GeolocationException;
use Bkhim\Geolocation\Providers\IpApi;
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
            'country' => 'United States',
            'latitude' => 37.386,
            'longitude' => -122.084,
            'timezone' => 'America/Los_Angeles',
            'utc_offset' => '-0800',
            'currency_name' => 'US Dollar',
            'currency' => 'USD',
            'continent_code' => 'NA',
            'postal' => '94043',
            'org' => 'AS15169 Google LLC',
        ]),
    ]);

    $cache = Cache::store();
    $provider = new IpApi($cache);

    $details = $provider->lookup('8.8.8.8');

    expect($details)->toBeInstanceOf(GeolocationDetails::class);
    expect($details->getIp())->toBe('8.8.8.8');
    expect($details->getCity())->toBe('Mountain View');
});

it('throws exception for invalid ip address', function () {
    $cache = Cache::store();
    $provider = new IpApi($cache);

    expect(fn () => $provider->lookup('invalid-ip'))
        ->toThrow(GeolocationException::class);
});

it('throws exception for error responses', function () {
    Http::fake([
        '*' => Http::response([
            'error' => true,
            'reason' => 'Bad Request',
        ]),
    ]);

    $cache = Cache::store();
    $provider = new IpApi($cache);

    expect(fn () => $provider->lookup('8.8.8.8'))
        ->toThrow(GeolocationException::class);
});

it('throws exception for missing ip in response', function () {
    Http::fake([
        '*' => Http::response([
            'city' => 'Mountain View',
            'country_code' => 'US',
        ]),
    ]);

    $cache = Cache::store();
    $provider = new IpApi($cache);

    expect(fn () => $provider->lookup('8.8.8.8'))
        ->toThrow(GeolocationException::class, 'Incomplete');
});

it('parses utc offset correctly', function () {
    Http::fake([
        '*' => Http::response([
            'ip' => '8.8.8.8',
            'country_code' => 'US',
            'latitude' => 37.386,
            'longitude' => -122.084,
            'utc_offset' => '-0800',
        ]),
    ]);

    $cache = Cache::store();
    $provider = new IpApi($cache);

    $details = $provider->lookup('8.8.8.8');

    expect($details->getTimezoneOffset())->toEqual(-8);
});

it('handles positive utc offset', function () {
    Http::fake([
        '*' => Http::response([
            'ip' => '8.8.8.8',
            'country_code' => 'JP',
            'latitude' => 35.6762,
            'longitude' => 139.6503,
            'utc_offset' => '+0900',
        ]),
    ]);

    $cache = Cache::store();
    $provider = new IpApi($cache);

    $details = $provider->lookup('8.8.8.8');

    expect($details->getTimezoneOffset())->toEqual(9);
});

it('transforms all response fields correctly', function () {
    Http::fake([
        '*' => Http::response([
            'ip' => '8.8.8.8',
            'city' => 'Mountain View',
            'region' => 'California',
            'country_code' => 'US',
            'country' => 'United States',
            'latitude' => 37.386,
            'longitude' => -122.084,
            'timezone' => 'America/Los_Angeles',
            'currency_name' => 'US Dollar',
            'currency' => 'USD',
            'continent_code' => 'NA',
            'postal' => '94043',
            'org' => 'AS15169 Google LLC',
        ]),
    ]);

    $cache = Cache::store();
    $provider = new IpApi($cache);

    $details = $provider->lookup('8.8.8.8');

    expect($details->getPostalCode())->toBe('94043');
    expect($details->getCurrencyCode())->toBe('USD');
    expect($details->getCurrency())->toBe('US Dollar');
});
