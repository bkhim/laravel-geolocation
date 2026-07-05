<?php

use Bkhim\Geolocation\Console\GeolocationCommand;
use Bkhim\Geolocation\Console\AuditCommand;
use Bkhim\Geolocation\GeolocationManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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

it('can run geolocation:lookup command with ip', function () {
    Http::fake([
        'ipinfo.io/*' => Http::response([
            'ip' => '8.8.8.8',
            'city' => 'Mountain View',
            'region' => 'California',
            'country_code' => 'US',
            'country' => 'US',
            'loc' => '37.3860,-122.0838',
            'org' => 'AS15169 Google LLC',
            'timezone' => 'America/Los_Angeles',
        ]),
    ]);

    config(['geolocation.cache.enabled' => false]);

    $this->artisan('geolocation:lookup', ['--ip' => '8.8.8.8'])
        ->expectsOutputToContain('8.8.8.8')
        ->assertSuccessful();
});

it('geolocation:lookup handles invalid ip gracefully', function () {
    $this->artisan('geolocation:lookup', ['--ip' => 'not-an-ip'])
        ->assertExitCode(1);
});

it('geolocation:lookup with --show-cache-info displays cache settings', function () {
    $this->artisan('geolocation:lookup', ['--show-cache-info' => true])
        ->expectsOutputToContain('Cache Configuration')
        ->assertSuccessful();
});

it('geolocation:audit handles empty database gracefully', function () {
    $this->artisan('geolocation:audit')
        ->expectsOutputToContain('No login data')
        ->assertSuccessful();
});

it('geolocation:audit shows login statistics', function () {
    $now = now();

    \Bkhim\Geolocation\Models\LoginHistory::create([
        'user_id' => 1,
        'ip' => '1.2.3.4',
        'country_code' => 'US',
        'city' => 'New York',
        'occurred_at' => $now,
    ]);

    \Bkhim\Geolocation\Models\LoginHistory::create([
        'user_id' => 1,
        'ip' => '5.6.7.8',
        'country_code' => 'KE',
        'city' => 'Nairobi',
        'is_proxy' => true,
        'occurred_at' => $now,
    ]);

    $this->artisan('geolocation:audit', ['--days' => 30])
        ->expectsOutputToContain('total logins')
        ->expectsOutputToContain('VPN/proxy')
        ->assertSuccessful();
});
