<?php

use Bkhim\Geolocation\GeolocationDetails;
use Bkhim\Geolocation\GeolocationException;
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
    expect($result)->toBeInstanceOf(GeolocationDetails::class);
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

it('builds cache keys using the configured prefix and provider', function () {
    config(['geolocation.cache.prefix' => 'myapp']);

    $manager = app('geolocation');

    expect($manager->getCacheKey('8.8.8.8', 'ipinfo'))
        ->toBe('myapp:ipinfo:'.md5('8.8.8.8'));
});

it('clears the exact cache entry that the provider wrote', function () {
    config([
        'geolocation.cache.enabled' => true,
        'geolocation.cache.prefix' => 'geo-test',
    ]);
    \Illuminate\Support\Facades\Cache::flush();

    \Illuminate\Support\Facades\Http::fake([
        'ipinfo.io/*' => \Illuminate\Support\Facades\Http::response([
            'ip' => '8.8.8.8',
            'country_code' => 'US',
            'country' => 'US',
            'loc' => '37.3860,-122.0838',
        ]),
    ]);

    $manager = app('geolocation');
    $manager->driver('ipinfo')->lookup('8.8.8.8');

    $key = $manager->getCacheKey('8.8.8.8', 'ipinfo');

    expect(\Illuminate\Support\Facades\Cache::has($key))->toBeTrue();

    $manager->clearCache('8.8.8.8', 'ipinfo');

    expect(\Illuminate\Support\Facades\Cache::has($key))->toBeFalse();
});

it('honours the fallback max_attempts count', function () {
    \Illuminate\Support\Facades\Http::fake([
        '*' => \Illuminate\Support\Facades\Http::response([], 500),
    ]);

    $config = array_merge(config('geolocation'), [
        'cache' => array_merge(config('geolocation.cache'), ['enabled' => false]),
        'fallback' => [
            'enabled' => true,
            'order' => ['ipstack', 'ipgeolocation'],
            'max_attempts' => 0,
        ],
    ]);

    $manager = new \Bkhim\Geolocation\GeolocationManager($config, \Illuminate\Support\Facades\Cache::driver());

    // max_attempts = 0 means: try the primary, then give up (no fallbacks).
    expect(fn () => $manager->lookup('8.8.8.8'))->toThrow(\Bkhim\Geolocation\GeolocationException::class);

    \Illuminate\Support\Facades\Http::assertSentCount(1);
});
