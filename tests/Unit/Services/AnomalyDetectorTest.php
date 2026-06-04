<?php

use Bkhim\Geolocation\Services\AnomalyDetector;
use Bkhim\Geolocation\Models\LoginHistory;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('user_login_locations', function ($table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('ip', 45)->nullable();
        $table->string('ip_hash', 64)->nullable();
        $table->char('country_code', 2)->nullable();
        $table->string('city')->nullable();
        $table->string('timezone')->nullable();
        $table->char('currency_code', 3)->nullable();
        $table->boolean('is_proxy')->default(false);
        $table->boolean('is_tor')->default(false);
        $table->timestamp('occurred_at');
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('user_login_locations');
});

it('calculates distance between two coordinates using haversine', function () {
    $detector = new AnomalyDetector();

    $distance = $detector->calculateDistance(
        40.7128, -74.0060,  // New York
        34.0522, -118.2437  // Los Angeles
    );

    expect($distance)->toBeGreaterThan(3900);
    expect($distance)->toBeLessThan(4000);
});

it('detects too many countries when exceeding limit', function () {
    $detector = new AnomalyDetector();

    $history = collect([
        (object)['country_code' => 'US'],
        (object)['country_code' => 'CA'],
        (object)['country_code' => 'MX'],
        (object)['country_code' => 'GB'],
    ]);

    $result = $detector->hasTooManyCountries($history);

    expect($result)->toBeTrue();
});

it('returns false for countries within limit', function () {
    $detector = new AnomalyDetector();

    $history = collect([
        (object)['country_code' => 'US'],
        (object)['country_code' => 'CA'],
    ]);

    $result = $detector->hasTooManyCountries($history);

    expect($result)->toBeFalse();
});

it('can configure max countries', function () {
    $detector = (new AnomalyDetector())->setMaxCountries(5);

    $history = collect([
        (object)['country_code' => 'US'],
        (object)['country_code' => 'CA'],
        (object)['country_code' => 'MX'],
        (object)['country_code' => 'GB'],
    ]);

    $result = $detector->hasTooManyCountries($history);
    expect($result)->toBeFalse();
});

it('saves and retrieves login history for user', function () {
    $history = LoginHistory::create([
        'user_id' => 1,
        'ip' => '1.2.3.4',
        'country_code' => 'US',
        'city' => 'New York',
        'occurred_at' => now(),
    ]);

    expect($history->id)->toBe(1);
    expect(LoginHistory::where('user_id', 1)->count())->toBe(1);
});

it('detects new country for user', function () {
    $detector = new AnomalyDetector();

    LoginHistory::create([
        'user_id' => 1,
        'ip' => '1.2.3.4',
        'country_code' => 'US',
        'occurred_at' => now()->subDays(5),
    ]);

    $countries = $detector->getUniqueCountries(1);
    expect($countries->contains('US'))->toBeTrue();
});

it('detects new city for user', function () {
    $detector = new AnomalyDetector();

    LoginHistory::create([
        'user_id' => 1,
        'ip' => '1.2.3.4',
        'country_code' => 'US',
        'city' => 'New York',
        'occurred_at' => now()->subDays(5),
    ]);

    $cities = $detector->getUniqueCities(1);
    expect($cities->contains('New York'))->toBeTrue();
});