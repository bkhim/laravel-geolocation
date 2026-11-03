<?php

use Bkhim\Geolocation\Traits\CachesGeolocationData;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Minimal host object exposing the trait's protected helpers for testing.
 */
function cacheTraitHost($cache = null)
{
    return new class($cache ?? Cache::store())
    {
        use CachesGeolocationData;

        public $cache;

        public function __construct($cache)
        {
            $this->cache = $cache;
        }

        public function remember(string $key, int $ttl, Closure $callback)
        {
            return $this->cacheRemember($key, $ttl, $callback);
        }
    };
}

beforeEach(function () {
    Cache::flush();
    config([
        'geolocation.cache.tags.enabled' => false,
        'geolocation.cache.sliding' => true,
    ]);
});

afterEach(function () {
    Carbon::setTestNow();
    Cache::flush();
});

it('remembers a value and returns the cached copy on subsequent calls', function () {
    $host = cacheTraitHost();
    $calls = 0;

    $first = $host->remember('geo:test', 100, function () use (&$calls) {
        $calls++;

        return 'value';
    });
    $second = $host->remember('geo:test', 100, function () use (&$calls) {
        $calls++;

        return 'value';
    });

    expect($first)->toBe('value')
        ->and($second)->toBe('value')
        ->and($calls)->toBe(1);
});

it('slides the ttl on access when sliding is enabled', function () {
    Carbon::setTestNow(now());

    $host = cacheTraitHost();
    $host->remember('geo:slide', 100, fn () => 'value');

    // Access again 80s later; sliding should push expiry to t=80+100=180.
    Carbon::setTestNow(now()->addSeconds(80));
    $host->remember('geo:slide', 100, fn () => 'value');

    // At t=150 a fixed TTL (t=100) would be gone; sliding keeps it alive.
    Carbon::setTestNow(now()->addSeconds(70));
    expect(Cache::has('geo:slide'))->toBeTrue();
});

it('does not extend the ttl when sliding is disabled', function () {
    config(['geolocation.cache.sliding' => false]);
    Carbon::setTestNow(now());

    $host = cacheTraitHost();
    $host->remember('geo:fixed', 100, fn () => 'value');

    Carbon::setTestNow(now()->addSeconds(80));
    $host->remember('geo:fixed', 100, fn () => 'value');

    Carbon::setTestNow(now()->addSeconds(70));
    expect(Cache::has('geo:fixed'))->toBeFalse();
});
