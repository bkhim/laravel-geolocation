<?php

use Bkhim\Geolocation\Geolocation;
use Bkhim\Geolocation\GeolocationManager;

it('registers geolocation manager in container', function () {
    $manager = app('geolocation');

    expect($manager)->toBeInstanceOf(GeolocationManager::class);
});

it('provides facade access', function () {
    expect(class_exists(Geolocation::class))->toBe(true);
});

it('retrieves countries translation', function () {
    $countries = Geolocation::countries();

    expect($countries)->toBeArray()
        ->and($countries)->toHaveKey('US');
});

it('creates storage directory if not exists', function () {
    $storagePath = storage_path('app/geoip');

    expect(is_dir($storagePath))->toBe(true);
});

it('loads translations', function () {
    $countries = trans('geolocation::countries');

    expect($countries)->toBeArray();
});

it('publishes configuration', function () {
    expect(config('geolocation.drivers'))->not->toBeNull();
});

it('can switch drivers via facade', function () {
    $driver = Geolocation::driver('ipapi');

    expect($driver)->not->toBeNull();
});

it('can resolve multiple drivers', function () {
    $ipapi = Geolocation::driver('ipapi');
    $ipinfo = Geolocation::driver('ipinfo');

    expect($ipapi)->not->toBeNull()
        ->and($ipinfo)->not->toBeNull();
});
