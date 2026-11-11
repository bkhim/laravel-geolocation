# Threat Intelligence

Detect proxies, VPNs, Tor exit nodes, and other threat indicators.

## Available Detection Methods

```php
$details = Geolocation::lookup($request->ip());

// Proxy/VPN detection
if ($details->isProxy()) {
    echo "User is using a proxy or VPN";
}

// Tor exit node
if ($details->isTor()) {
    echo "User is on Tor network";
}

// Bot/crawler detection
if ($details->isCrawler()) {
    echo "User is a web crawler or bot";
}

// Mobile connection
if ($details->isMobile()) {
    echo "User is on mobile connection";
}
```

## Providers Supporting Detection

| Provider | Proxy/VPN | Tor | Crawler | Mobile |
|----------|-----------|-----|---------|--------|
| IP2Location.io | ✅ | ✅ | ✅ | ✅ |
| IPGeolocation | ✅ | ✅ | ❌ | ✅ |
| IpInfo (Plus) | ✅ | ❌ | ❌ | ❌ |
| ipapi.co | ❌ | ❌ | ❌ | ❌ |
| MaxMind | ❌ | ❌ | ❌ | ❌ |
| IPStack | ❌ | ❌ | ❌ | ❌ |

## Practical Usage

### Login Security

```php
public function login(Request $request)
{
    $details = Geolocation::lookup($request->ip());
    
    $threats = [];
    
    if ($details->isProxy()) {
        $threats[] = 'proxy';
    }
    
    if ($details->isTor()) {
        $threats[] = 'tor';
    }
    
    if ($details->isCrawler()) {
        $threats[] = 'crawler';
    }
    
    if (!empty($threats)) {
        Log::warning('Login from suspicious IP', [
            'ip' => $request->ip(),
            'threats' => $threats,
            'details' => $details->toArray(),
        ]);
        
        // Optional: Block or require additional verification
    }
}
```

### Rate Limiting by Threat Level

```php
Route::middleware('geo.ratelimit:60,1')->group(function () {
    // Normal routes
});

Route::middleware('geo.ratelimit:10,1')->when(function ($request) {
    $details = Geolocation::lookup($request->ip());
    return $details->isProxy() || $details->isTor();
})->group(function () {
    // Stricter limits for suspicious IPs
});
```

### Connection Type

```php
$details = Geolocation::lookup($request->ip());

$type = $details->getConnectionType();

// Options: residential, corporate, datacenter, dialup, satellite, broadband
echo "Connection type: " . $type;

// Datacenter IPs are often suspicious for user logins
if ($type === 'datacenter') {
    // Flag for review
}
```

## AbuseIPDB reputation checks

```php
use Bkhim\Geolocation\Services\ThreatIntelligenceService;

$service = app(ThreatIntelligenceService::class);

$service->checkIp('1.2.3.4');   // ?array — cached reputation data
$service->isThreat('1.2.3.4');  // bool — score >= min_confidence_score
$service->forget('1.2.3.4');    // drop the cached value
```

Configuration:

```env
GEOLOCATION_THREAT_INTELLIGENCE_ENABLED=true
ABUSEIPDB_API_KEY=your_key
GEOLOCATION_THREAT_MIN_SCORE=50
GEOLOCATION_THREAT_CACHE_TTL=3600
GEOLOCATION_THREAT_FAILURE_TTL=300
GEOLOCATION_THREAT_AUTO_BLOCK=false
```

Successful lookups are cached for `cache_ttl`; failures are negative-cached for
`failure_ttl` so an API outage does not trigger a request per page view.

## Events

Security events are dispatched automatically for recorded logins (the package
registers a single listener). Add your own:

```php
use Illuminate\Support\Facades\Event;
use Bkhim\Geolocation\Events\HighRiskIpDetected;

Event::listen(HighRiskIpDetected::class, App\Listeners\LogHighRiskLogin::class);
```

## Best Practices

1. **Log but don't block** - Log suspicious activity for review, don't auto-block unless critical
2. **Use layers** - Combine proxy detection with other signals (failed attempts, new device)
3. **Whitelist known IPs** - Allow known corporate VPNs, mobile carriers
4. **Monitor trends** - Track threat data over time to identify patterns
