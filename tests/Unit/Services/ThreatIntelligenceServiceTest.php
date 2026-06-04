<?php

use Bkhim\Geolocation\Services\ThreatIntelligenceService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

it('returns null when no api key configured', function () {
    Config::set('geolocation.threat_intelligence.abuseipdb_api_key', null);

    $service = new ThreatIntelligenceService();
    $result = $service->checkIp('1.2.3.4');

    expect($result)->toBeNull();
});

it('uses cache when available', function () {
    Config::set('geolocation.threat_intelligence.abuseipdb_api_key', 'test_key');

    Cache::shouldReceive('remember')
        ->once()
        ->andReturn(['abuseConfidenceScore' => 75]);

    $service = new ThreatIntelligenceService();
    $result = $service->checkIp('1.2.3.4');

    expect($result['abuseConfidenceScore'])->toBe(75);
});

it('is threat returns true when above threshold', function () {
    Config::set('geolocation.threat_intelligence.abuseipdb_api_key', 'test_key');
    Config::set('geolocation.threat_intelligence.min_confidence_score', 50);

    Cache::shouldReceive('remember')
        ->once()
        ->andReturn(['abuseConfidenceScore' => 75]);

    $service = new ThreatIntelligenceService();
    $result = $service->isThreat('1.2.3.4');

    expect($result)->toBeTrue();
});

it('is threat returns false when below threshold', function () {
    Config::set('geolocation.threat_intelligence.abuseipdb_api_key', 'test_key');
    Config::set('geolocation.threat_intelligence.min_confidence_score', 50);

    Cache::shouldReceive('remember')
        ->once()
        ->andReturn(['abuseConfidenceScore' => 25]);

    $service = new ThreatIntelligenceService();
    $result = $service->isThreat('1.2.3.4');

    expect($result)->toBeFalse();
});