<?php

use Bkhim\Geolocation\Models\IpBlocklist;
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

it('accepts any DateTimeInterface implementation', function () {
    $result = IpBlocklist::block('2.2.2.2', 'Immutable test', new \DateTimeImmutable('+1 hour'));

    expect(IpBlocklist::isBlocked('2.2.2.2'))->toBeTrue()
        ->and($result->ip)->toBe('2.2.2.2');
});

it('can unblock an ip immediately', function () {
    IpBlocklist::block('3.3.3.3', 'Test', now()->addDay()->toDateTime());

    expect(IpBlocklist::unblock('3.3.3.3'))->toBeTrue()
        ->and(IpBlocklist::isBlocked('3.3.3.3'))->toBeFalse();
});

it('prunes expired entries only', function () {
    IpBlocklist::block('4.4.4.4', 'Expired', now()->subHour()->toDateTime());
    IpBlocklist::block('5.5.5.5', 'Active', now()->addHour()->toDateTime());

    $deleted = IpBlocklist::pruneExpired();

    expect($deleted)->toBe(1)
        ->and(IpBlocklist::where('ip', '4.4.4.4')->exists())->toBeFalse()
        ->and(IpBlocklist::where('ip', '5.5.5.5')->exists())->toBeTrue();
});
