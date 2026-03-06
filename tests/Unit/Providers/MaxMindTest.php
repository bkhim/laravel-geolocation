<?php

use Bkhim\Geolocation\GeolocationException;
use Bkhim\Geolocation\Providers\MaxMind;
use GeoIp2\Database\Reader;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

it('throws exception for invalid ip address', function () {
    $mockReader = \Mockery::mock(Reader::class);
    $mockReader->shouldReceive('city')
        ->andThrow(new \Exception('Invalid IP'));

    $cache = Cache::driver();
    $provider = new MaxMind($mockReader, $cache);

    expect(fn () => $provider->lookup('invalid-ip'))
        ->toThrow(GeolocationException::class);
});

it('skips cache when caching is disabled', function () {
    $mockReader = \Mockery::mock(Reader::class);
    $mockReader->shouldReceive('city')
        ->twice()
        ->andThrow(new \GeoIp2\Exception\AddressNotFoundException('Not found'));

    $cache = Cache::driver();
    $provider = new MaxMind($mockReader, $cache);

    config(['geolocation.cache.enabled' => false]);

    // First call should throw
    expect(fn () => $provider->lookup('8.8.8.8'))->toThrow(GeolocationException::class);
    
    // Second call should also throw (not served from cache)
    expect(fn () => $provider->lookup('8.8.8.8'))->toThrow(GeolocationException::class);
});
