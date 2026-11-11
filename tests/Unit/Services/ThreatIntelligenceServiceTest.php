<?php

use Bkhim\Geolocation\Services\ThreatIntelligenceService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
});

it('returns null when no api key configured', function () {
    Config::set('geolocation.threat_intelligence.abuseipdb_api_key', null);

    $service = new ThreatIntelligenceService;
    $result = $service->checkIp('1.2.3.4');

    expect($result)->toBeNull();
});

it('returns cached reputation data without calling the api', function () {
    Config::set('geolocation.threat_intelligence.abuseipdb_api_key', 'test_key');

    Cache::put('threat:abuseipdb:1.2.3.4', ['abuseConfidenceScore' => 75], 3600);
    Http::fake();

    $service = new ThreatIntelligenceService;
    $result = $service->checkIp('1.2.3.4');

    expect($result['abuseConfidenceScore'])->toBe(75);
    Http::assertNothingSent();
});

it('caches a successful lookup', function () {
    Config::set('geolocation.threat_intelligence.abuseipdb_api_key', 'test_key');

    Http::fake([
        'api.abuseipdb.com/*' => Http::response([
            'data' => ['abuseConfidenceScore' => 88, 'ipAddress' => '1.2.3.4'],
        ]),
    ]);

    $service = new ThreatIntelligenceService;
    $result = $service->checkIp('1.2.3.4');

    expect($result['abuseConfidenceScore'])->toBe(88)
        ->and(Cache::get('threat:abuseipdb:1.2.3.4')['abuseConfidenceScore'])->toBe(88);
});

it('negative-caches a failed lookup so it is not retried immediately', function () {
    Config::set('geolocation.threat_intelligence.abuseipdb_api_key', 'test_key');

    Http::fake([
        'api.abuseipdb.com/*' => Http::response([], 500),
    ]);

    $service = new ThreatIntelligenceService;

    expect($service->checkIp('1.2.3.4'))->toBeNull();
    expect($service->checkIp('1.2.3.4'))->toBeNull();

    Http::assertSentCount(1);
});

it('is threat returns true when above threshold', function () {
    Config::set('geolocation.threat_intelligence.abuseipdb_api_key', 'test_key');
    Config::set('geolocation.threat_intelligence.min_confidence_score', 50);

    Cache::put('threat:abuseipdb:1.2.3.4', ['abuseConfidenceScore' => 75], 3600);

    $service = new ThreatIntelligenceService;

    expect($service->isThreat('1.2.3.4'))->toBeTrue();
});

it('is threat returns false when below threshold', function () {
    Config::set('geolocation.threat_intelligence.abuseipdb_api_key', 'test_key');
    Config::set('geolocation.threat_intelligence.min_confidence_score', 50);

    Cache::put('threat:abuseipdb:1.2.3.4', ['abuseConfidenceScore' => 25], 3600);

    $service = new ThreatIntelligenceService;

    expect($service->isThreat('1.2.3.4'))->toBeFalse();
});

it('forgets a cached reputation', function () {
    Cache::put('threat:abuseipdb:1.2.3.4', ['abuseConfidenceScore' => 75], 3600);

    $service = new ThreatIntelligenceService;

    expect($service->forget('1.2.3.4'))->toBeTrue()
        ->and(Cache::get('threat:abuseipdb:1.2.3.4'))->toBeNull();
});
