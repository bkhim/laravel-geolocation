<?php

use Bkhim\Geolocation\Addons\Middleware\GeoRateLimitMiddleware;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Install a fresh HTTP factory and fake an ipinfo response for a country.
 *
 * A fresh factory per test avoids Laravel's fake() merging stubs with the
 * previous test's stubs (which would keep returning the first match).
 */
function fakeGeoResponse(string $countryCode, string $ip = '8.8.8.8', int $status = 200): void
{
    Http::swap(new Factory);

    $body = $status === 200
        ? ['ip' => $ip, 'country_code' => $countryCode, 'country' => $countryCode, 'loc' => '0,0']
        : [];

    Http::fake(['ipinfo.io/*' => Http::response($body, $status)]);
}

beforeEach(function () {
    RateLimiter::clear('*');
    config([
        'geolocation.cache.enabled' => false,
        'geolocation.addons.rate_limiting.enabled' => true,
        'geolocation.addons.rate_limiting.limits' => [
            'US' => ['requests_per_minute' => 2],
            'DE' => 5,
            '*' => ['requests_per_minute' => 3],
        ],
    ]);
});

it('parses the documented array limit shape without a TypeError', function () {
    fakeGeoResponse('US');

    $middleware = new GeoRateLimitMiddleware;
    $request = Request::create('/test', 'GET', [], [], [], ['REMOTE_ADDR' => '8.8.8.8']);

    $response = $middleware->handle($request, fn () => response('ok', 200));

    expect($response->getStatusCode())->toBe(200)
        ->and($response->headers->get('X-RateLimit-Limit'))->toBe('2');
});

it('allows requests up to the country limit then returns 429', function () {
    fakeGeoResponse('US');

    $middleware = new GeoRateLimitMiddleware;
    $request = Request::create('/test', 'GET', [], [], [], ['REMOTE_ADDR' => '8.8.8.8']);

    expect($middleware->handle($request, fn () => response('ok'))->getStatusCode())->toBe(200);
    expect($middleware->handle($request, fn () => response('ok'))->getStatusCode())->toBe(200);

    $blocked = $middleware->handle($request, fn () => response('ok'));

    expect($blocked->getStatusCode())->toBe(429)
        ->and($blocked->headers->get('Retry-After'))->not->toBeNull();
});

it('accepts a plain integer limit', function () {
    fakeGeoResponse('DE', '9.9.9.9');

    $middleware = new GeoRateLimitMiddleware;
    $request = Request::create('/test', 'GET', [], [], [], ['REMOTE_ADDR' => '9.9.9.9']);

    $response = $middleware->handle($request, fn () => response('ok'));

    expect($response->headers->get('X-RateLimit-Limit'))->toBe('5');
});

it('falls back to the wildcard limit for unknown countries', function () {
    fakeGeoResponse('ZZ', '203.0.113.9');

    $middleware = new GeoRateLimitMiddleware;
    $request = Request::create('/test', 'GET', [], [], [], ['REMOTE_ADDR' => '203.0.113.9']);

    $response = $middleware->handle($request, fn () => response('ok'));

    expect($response->headers->get('X-RateLimit-Limit'))->toBe('3');
});

it('passes through untouched when rate limiting is disabled', function () {
    config(['geolocation.addons.rate_limiting.enabled' => false]);

    $middleware = new GeoRateLimitMiddleware;
    $request = Request::create('/test', 'GET', [], [], [], ['REMOTE_ADDR' => '8.8.8.8']);

    $response = $middleware->handle($request, fn () => response('ok'));

    expect($response->getStatusCode())->toBe(200)
        ->and($response->headers->has('X-RateLimit-Limit'))->toBeFalse();
});

it('does not break when the geolocation lookup fails', function () {
    fakeGeoResponse('US', '8.8.8.8', 500);

    $middleware = new GeoRateLimitMiddleware;
    $request = Request::create('/test', 'GET', [], [], [], ['REMOTE_ADDR' => '8.8.8.8']);

    $response = $middleware->handle($request, fn () => response('ok'));

    expect($response->getStatusCode())->toBe(200)
        ->and($response->headers->get('X-RateLimit-Limit'))->toBe('3');
});
