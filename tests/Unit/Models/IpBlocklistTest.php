<?php

use Bkhim\Geolocation\Models\IpBlocklist;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('geolocation_ip_blocklist', function ($table) {
        $table->id();
        $table->string('ip', 45)->unique();
        $table->string('reason')->nullable();
        $table->timestamp('blocked_until');
        $table->integer('attempts')->default(1);
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('geolocation_ip_blocklist');
});

it('blocks an ip address', function () {
    $result = IpBlocklist::block('1.2.3.4', 'Test block');

    expect($result->ip)->toBe('1.2.3.4');
    expect($result->reason)->toBe('Test block');
    expect($result->attempts)->toBe(1);
});

it('increments attempts on repeated block', function () {
    IpBlocklist::block('1.2.3.4', 'First');
    IpBlocklist::block('1.2.3.4', 'Second');

    $record = IpBlocklist::where('ip', '1.2.3.4')->first();
    expect($record->attempts)->toBe(2);
});

it('is blocked returns true for active block', function () {
    IpBlocklist::block('1.2.3.4', 'Test', now()->addHour()->toDateTime());

    expect(IpBlocklist::isBlocked('1.2.3.4'))->toBeTrue();
});

it('is blocked returns false for expired block', function () {
    IpBlocklist::block('1.2.3.4', 'Test', now()->subHour()->toDateTime());

    expect(IpBlocklist::isBlocked('1.2.3.4'))->toBeFalse();
});

it('is blocked returns false for non-blocked ip', function () {
    expect(IpBlocklist::isBlocked('5.6.7.8'))->toBeFalse();
});