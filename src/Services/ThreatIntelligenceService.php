<?php

namespace Bkhim\Geolocation\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ThreatIntelligenceService
{
    protected ?string $apiKey;

    protected int $cacheTtl = 3600;

    /**
     * How long to remember a failed/empty lookup, in seconds.
     *
     * Prevents hammering the API for an IP that just errored (null results
     * are not cached by Laravel's remember(), so failures would otherwise be
     * retried on every request).
     */
    protected int $failureTtl = 300;

    public function __construct()
    {
        $this->apiKey = config('geolocation.threat_intelligence.abuseipdb_api_key');
        $this->cacheTtl = (int) config('geolocation.threat_intelligence.cache_ttl', $this->cacheTtl);
        $this->failureTtl = (int) config('geolocation.threat_intelligence.failure_ttl', $this->failureTtl);
    }

    public function checkIp(string $ip): ?array
    {
        if (! $this->apiKey) {
            return null;
        }

        $cacheKey = "threat:abuseipdb:{$ip}";

        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        // Negative-cache marker: we recently failed, don't retry immediately.
        if ($cached === false) {
            return null;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Key' => $this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->get('https://api.abuseipdb.com/api/v2/check', [
                    'ipAddress' => $ip,
                    'maxAgeInDays' => 90,
                ]);

            $data = $response->successful() ? $response->json('data') : null;

            if (is_array($data)) {
                Cache::put($cacheKey, $data, $this->cacheTtl);

                return $data;
            }

            Cache::put($cacheKey, false, $this->failureTtl);

            return null;
        } catch (\Throwable $e) {
            report($e);

            Cache::put($cacheKey, false, $this->failureTtl);

            return null;
        }
    }

    public function isThreat(string $ip): bool
    {
        $data = $this->checkIp($ip);

        if (! $data) {
            return false;
        }

        $confidence = $data['abuseConfidenceScore'] ?? 0;
        $minScore = (int) config('geolocation.threat_intelligence.min_confidence_score', 50);

        return $confidence >= $minScore;
    }

    public function getThreatDetails(string $ip): ?array
    {
        return $this->checkIp($ip);
    }

    /**
     * Forget the cached reputation for an IP.
     */
    public function forget(string $ip): bool
    {
        return Cache::forget("threat:abuseipdb:{$ip}");
    }
}
