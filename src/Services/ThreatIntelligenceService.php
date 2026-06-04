<?php

namespace Bkhim\Geolocation\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ThreatIntelligenceService
{
    protected ?string $apiKey;
    protected int $cacheTtl = 3600;

    public function __construct()
    {
        $this->apiKey = config('geolocation.threat_intelligence.abuseipdb_api_key');
    }

    public function checkIp(string $ip): ?array
    {
        if (!$this->apiKey) {
            return null;
        }

        $cacheKey = "threat:abuseipdb:{$ip}";
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($ip) {
            try {
                $response = Http::timeout(10)
                    ->withHeaders([
                        'Key' => $this->apiKey,
                        'Accept' => 'application/json',
                    ])
                    ->get("https://api.abuseipdb.com/api/v2/check", [
                        'ipAddress' => $ip,
                        'maxAgeInDays' => 90,
                    ]);

                if ($response->successful()) {
                    return $response->json('data');
                }
            } catch (\Exception $e) {
                report($e);
            }
            
            return null;
        });
    }

    public function isThreat(string $ip): bool
    {
        $data = $this->checkIp($ip);
        
        if (!$data) {
            return false;
        }

        $confidence = $data['abuseConfidenceScore'] ?? 0;
        $minScore = config('geolocation.threat_intelligence.min_confidence_score', 50);

        return $confidence >= $minScore;
    }

    public function getThreatDetails(string $ip): ?array
    {
        return $this->checkIp($ip);
    }
}