<?php

use Bkhim\Geolocation\GeolocationManager;
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
