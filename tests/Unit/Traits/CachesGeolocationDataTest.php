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
    // Cache::touch() ships in Laravel 13; on Laravel 12 (PHP 8.2 CI job) the
    // trait falls back to a fixed TTL by design, so there is nothing to slide.
    if (! method_exists(Cache::store(), 'touch')) {
        $this->markTestSkipped('Cache::touch() requires Laravel 13+; Laravel 12 uses a fixed TTL.');
    }

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

it('falls back to a fixed ttl when the store has no touch() support', function () {
    // A minimal store that only implements remember() and has no touch(),
    // mimicking Laravel 12 / any store without touch support.
    $store = new class
    {
        public int $rememberCalls = 0;

        public function remember(string $key, int $ttl, \Closure $callback)
        {
            $this->rememberCalls++;

            return $callback();
        }
    };

    $host = cacheTraitHost($store);

    expect($host->remember('geo:notouch', 100, fn () => 'value'))->toBe('value')
        ->and($store->rememberCalls)->toBe(1);
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
