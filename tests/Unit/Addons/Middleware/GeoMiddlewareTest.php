<?php

use Bkhim\Geolocation\Addons\Middleware\GeoMiddleware;
use Bkhim\Geolocation\Addons\Middleware\SecurityCheckMiddleware;
use Bkhim\Geolocation\Models\IpBlocklist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;

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

it('geo allow middleware passes for allowed country', function () {
    Http::fake([
        'ipinfo.io/*' => Http::response([
            'ip' => '8.8.8.8',
            'country_code' => 'US',
            'country' => 'US',
            'loc' => '37.3860,-122.0838',
        ]),
    ]);

    config(['geolocation.cache.enabled' => false]);

    $middleware = new GeoMiddleware();
    $request = Request::create('/test', 'GET', [], [], [], ['REMOTE_ADDR' => '8.8.8.8']);

    $response = $middleware->handle($request, function ($req) {
        return response('allowed', 200);
    }, 'allow', 'US,CA');

    expect($response->getStatusCode())->toBe(200);
    expect($response->getContent())->toBe('allowed');
});

it('geo deny middleware blocks denied country', function () {
    Http::fake([
        'ipinfo.io/*' => Http::response([
            'ip' => '8.8.8.8',
            'country_code' => 'US',
            'country' => 'US',
            'loc' => '37.3860,-122.0838',
        ]),
    ]);

    config(['geolocation.cache.enabled' => false]);

    $this->expectException(HttpException::class);

    $middleware = new GeoMiddleware();
    $request = Request::create('/test', 'GET', [], [], [], ['REMOTE_ADDR' => '8.8.8.8']);

    $middleware->handle($request, function ($req) {
        return response('allowed', 200);
    }, 'deny', 'US');
});

it('geo allow middleware blocks disallowed country', function () {
    Http::fake([
        'ipinfo.io/*' => Http::response([
            'ip' => '8.8.8.8',
            'country_code' => 'US',
            'country' => 'US',
            'loc' => '37.3860,-122.0838',
        ]),
    ]);

    config(['geolocation.cache.enabled' => false]);

    $this->expectException(HttpException::class);

    $middleware = new GeoMiddleware();
    $request = Request::create('/test', 'GET', [], [], [], ['REMOTE_ADDR' => '8.8.8.8']);

    $middleware->handle($request, function ($req) {
        return response('allowed', 200);
    }, 'allow', 'KE,GB');
});

it('geo middleware passes when no locations specified', function () {
    $middleware = new GeoMiddleware();
    $request = Request::create('/test', 'GET', [], [], [], ['REMOTE_ADDR' => '8.8.8.8']);

    $response = $middleware->handle($request, function ($req) {
        return response('allowed', 200);
    }, 'allow');

    expect($response->getStatusCode())->toBe(200);
});

it('geo middleware returns json for api requests', function () {
    Http::fake([
        'ipinfo.io/*' => Http::response([
            'ip' => '8.8.8.8',
            'country_code' => 'US',
            'country' => 'US',
            'loc' => '37.3860,-122.0838',
        ]),
    ]);

    config(['geolocation.cache.enabled' => false]);

    $middleware = new GeoMiddleware();
    $request = Request::create('/api/test', 'GET', [], [], [], ['REMOTE_ADDR' => '8.8.8.8', 'HTTP_ACCEPT' => 'application/json']);

    $response = $middleware->handle($request, function ($req) {
        return response('allowed', 200);
    }, 'allow', 'KE');

    expect($response->getStatusCode())->toBe(403);
    expect($response->headers->get('Content-Type'))->toContain('application/json');
});

it('security check middleware passes for non-blocked ip', function () {
    config(['geolocation.security.enable_blocking' => true]);
    config(['geolocation.threat_intelligence.enabled' => false]);

    $middleware = new SecurityCheckMiddleware();
    $request = Request::create('/test', 'GET', [], [], [], ['REMOTE_ADDR' => '8.8.8.8']);

    $response = $middleware->handle($request, function ($req) {
        return response('allowed', 200);
    });

    expect($response->getStatusCode())->toBe(200);
});

it('security check middleware blocks blocked ip', function () {
    config(['geolocation.security.enable_blocking' => true]);
    config(['geolocation.threat_intelligence.enabled' => false]);

    IpBlocklist::block('1.2.3.4', 'Test block', now()->addHour()->toDateTime());

    $middleware = new SecurityCheckMiddleware();
    $request = Request::create('/test', 'GET', [], [], [], ['REMOTE_ADDR' => '1.2.3.4']);

    $response = $middleware->handle($request, function ($req) {
        return response('allowed', 200);
    });

    expect($response->getStatusCode())->toBe(403);
});

it('security check middleware respects enable_blocking config', function () {
    config(['geolocation.security.enable_blocking' => false]);

    IpBlocklist::block('1.2.3.4', 'Test block', now()->addHour()->toDateTime());

    $middleware = new SecurityCheckMiddleware();
    $request = Request::create('/test', 'GET', [], [], [], ['REMOTE_ADDR' => '1.2.3.4']);

    $response = $middleware->handle($request, function ($req) {
        return response('allowed', 200);
    });

    expect($response->getStatusCode())->toBe(200);
});
