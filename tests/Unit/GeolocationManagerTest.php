<?php

use Bkhim\Geolocation\GeolocationManager;
use Bkhim\Geolocation\GeolocationException;
use Illuminate\Support\Facades\Cache;


it('can instantiate geolocation manager', function () {
    $config = config('geolocation');
    $cache = Cache::driver();

    $manager = new GeolocationManager($config, $cache);

    expect($manager)->toBeInstanceOf(GeolocationManager::class);
});

it('loads default driver', function () {
    $manager = app('geolocation');
    $driver = $manager->driver();

    expect($driver)->not->toBeNull();
});

it('can switch between drivers', function () {
    $manager = app('geolocation');

    $ipApiDriver = $manager->driver('ipapi');
    expect($ipApiDriver)->not->toBeNull();
});

it('throws exception for invalid driver', function () {
    $manager = app('geolocation');

    expect(fn () => $manager->driver('nonexistent'))
        ->toThrow(InvalidArgumentException::class);
});

it('caches driver instances', function () {
    $manager = app('geolocation');

    $driver1 = $manager->driver('ipapi');
    $driver2 = $manager->driver('ipapi');

    // Should return the same cached instance
    expect($driver1)->toBe($driver2);
});

it('provides facade access', function () {
    $manager = app('geolocation');

    expect($manager)->toBeInstanceOf(GeolocationManager::class);
});

it('dynamically calls driver methods', function () {
    $manager = app('geolocation');

    // Test that manager has the lookup method via __call
    expect(method_exists($manager, '__call'))->toBeTrue();
});

it('uses fallback when primary provider fails', function () {
    $config = array_merge(config('geolocation'), [
        'fallback' => [
            'enabled' => true,
            'order' => ['ipapi'],
            'max_attempts' => 2,
        ],
    ]);
    $cache = Cache::driver();
    $manager = new GeolocationManager($config, $cache);

    // When fallback is enabled and primary (ipinfo) fails,
    // it should try ipapi which doesn't need an API key
    $result = $manager->lookup('8.8.8.8');
    expect($result)->toBeInstanceOf(\Bkhim\Geolocation\GeolocationDetails::class);
    expect($result->getCountryCode())->toBe('US');
});

it('throws exception when all providers fail', function () {
    $config = array_merge(config('geolocation'), [
        'fallback' => [
            'enabled' => true,
            'order' => [],
            'max_attempts' => 1,
        ],
    ]);
    $cache = Cache::driver();
    $manager = new GeolocationManager($config, $cache);

    expect(fn () => $manager->lookup('8.8.8.8'))
        ->toThrow(GeolocationException::class);
});
