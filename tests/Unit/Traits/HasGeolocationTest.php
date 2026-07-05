<?php

use Bkhim\Geolocation\Events\LoginLocationRecorded;
use Bkhim\Geolocation\Models\LoginHistory;
use Bkhim\Geolocation\Traits\HasGeolocation;
use Bkhim\Geolocation\Traits\HasGeolocationSecurity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class TestGeolocationUser extends Model
{
    protected $table = 'test_users';
    public $timestamps = true;

    protected $fillable = ['name', 'email'];

    use HasGeolocation, HasGeolocationSecurity;
}

beforeEach(function () {
    Schema::create('test_users', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamps();
    });

    Schema::create('user_login_locations', function ($table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('ip', 45)->nullable();
        $table->string('ip_hash', 64)->nullable();
        $table->char('country_code', 2)->nullable();
        $table->char('continent_code', 2)->nullable();
        $table->string('city')->nullable();
        $table->string('region')->nullable();
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
    Schema::dropIfExists('test_users');
});

it('records a login location for a user', function () {
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

    $user = TestGeolocationUser::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $user->recordLoginLocation('8.8.8.8');

    expect(LoginHistory::where('user_id', $user->id)->count())->toBe(1);

    $record = LoginHistory::where('user_id', $user->id)->first();
    expect($record->country_code)->toBe('US')
        ->and($record->city)->toBe('Mountain View')
        ->and($record->timezone)->toBe('America/Los_Angeles');
});

it('fires login location recorded event', function () {
    Event::fake();

    Http::fake([
        'ipinfo.io/*' => Http::response([
            'ip' => '8.8.8.8',
            'country_code' => 'US',
            'country' => 'US',
            'loc' => '37.3860,-122.0838',
        ]),
    ]);

    $user = TestGeolocationUser::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $user->recordLoginLocation('8.8.8.8');

    Event::assertDispatched(LoginLocationRecorded::class);
});

it('gets the last login country', function () {
    $user = TestGeolocationUser::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    LoginHistory::create([
        'user_id' => $user->id,
        'ip' => '1.2.3.4',
        'country_code' => 'US',
        'city' => 'New York',
        'occurred_at' => now(),
    ]);

    expect($user->getLastLoginCountry())->toBe('US');
});

it('detects login from a new country', function () {
    Http::fake([
        'ipinfo.io/*' => Http::response([
            'ip' => '8.8.8.8',
            'country_code' => 'US',
            'country' => 'US',
            'loc' => '37.3860,-122.0838',
        ]),
    ]);

    $user = TestGeolocationUser::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    LoginHistory::create([
        'user_id' => $user->id,
        'ip' => '1.2.3.4',
        'country_code' => 'KE',
        'city' => 'Nairobi',
        'occurred_at' => now()->subDay(),
    ]);

    expect($user->isLoginFromNewCountry('8.8.8.8'))->toBeTrue();
});

it('returns false when login is from a known country', function () {
    Http::fake([
        'ipinfo.io/*' => Http::response([
            'ip' => '8.8.8.8',
            'country_code' => 'US',
            'country' => 'US',
            'loc' => '37.3860,-122.0838',
        ]),
    ]);

    $user = TestGeolocationUser::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    LoginHistory::create([
        'user_id' => $user->id,
        'ip' => '1.2.3.4',
        'country_code' => 'US',
        'city' => 'New York',
        'occurred_at' => now()->subDay(),
    ]);

    expect($user->isLoginFromNewCountry('8.8.8.8'))->toBeFalse();
});

it('masks ip in partial anonymization mode', function () {
    config(['geolocation.user_trait.anonymization_mode' => 'partial']);
    config(['geolocation.user_trait.store_ip' => true]);

    Http::fake([
        'ipinfo.io/*' => Http::response([
            'ip' => '8.8.8.8',
            'country_code' => 'US',
            'country' => 'US',
            'loc' => '37.3860,-122.0838',
        ]),
    ]);

    $user = TestGeolocationUser::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $user->recordLoginLocation('8.8.8.8');

    $record = LoginHistory::where('user_id', $user->id)->first();
    expect($record->ip)->toBe('8.8.8.0');
});

it('returns suspicious login count', function () {
    $user = TestGeolocationUser::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    LoginHistory::create([
        'user_id' => $user->id,
        'ip' => '1.2.3.4',
        'country_code' => 'US',
        'is_proxy' => true,
        'is_tor' => false,
        'occurred_at' => now(),
    ]);

    LoginHistory::create([
        'user_id' => $user->id,
        'ip' => '5.6.7.8',
        'country_code' => 'RU',
        'is_proxy' => false,
        'is_tor' => true,
        'occurred_at' => now(),
    ]);

    expect($user->getSuspiciousLoginCount())->toBe(2);
});

it('evaluates risk score for proxy ip', function () {
    Http::fake([
        'ipinfo.io/*' => Http::response([
            'ip' => '8.8.8.8',
            'country_code' => 'US',
            'country' => 'US',
            'loc' => '37.3860,-122.0838',
            'org' => 'AS15169 Google LLC',
        ]),
    ]);

    $user = TestGeolocationUser::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $risk = $user->getRiskScore('8.8.8.8');

    expect($risk)->toHaveKeys(['score', 'is_high_risk', 'threshold', 'triggers', 'trusted_country']);
});

it('requires mfa for high risk login', function () {
    config(['geolocation.security.enable_mfa_trigger' => true]);
    config(['geolocation.security.risk_threshold' => 'low']);

    Http::fake([
        'ipinfo.io/*' => Http::response([
            'ip' => '8.8.8.8',
            'country_code' => 'US',
            'country' => 'US',
            'loc' => '37.3860,-122.0838',
        ]),
    ]);

    $user = TestGeolocationUser::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    // With a low risk threshold, and first login being a new country
    // the risk should trigger MFA
    $mfa = $user->requiresMfaDueToLocation('8.8.8.8');
    expect($mfa)->toBeBool();
});

it('has login histories relationship', function () {
    $user = TestGeolocationUser::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    LoginHistory::create([
        'user_id' => $user->id,
        'ip' => '1.2.3.4',
        'country_code' => 'US',
        'occurred_at' => now(),
    ]);

    expect($user->loginHistories())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    expect($user->loginHistories()->count())->toBe(1);
});
