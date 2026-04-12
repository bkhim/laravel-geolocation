# Security Overview

This package provides enterprise-grade security features for Laravel applications. This guide covers all security features in one place.

---

## Quick Setup

### 1. Configure Security Settings

In `.env`:

```env
# Enable security features
GEOLOCATION_SECURITY_MFA_ENABLED=true
GEOLOCATION_SECURITY_BLOCKING_ENABLED=true

# Threat intelligence (optional - requires AbuseIPDB API key)
GEOLOCATION_THREAT_INTELLIGENCE_ENABLED=true
ABUSEIPDB_API_KEY=your_api_key
GEOLOCATION_THREAT_MIN_SCORE=50

# Data retention (days)
GEOLOCATION_LOGIN_RETENTION_DAYS=30
```

### 2. Publish Migrations

```bash
php artisan migrate
```

This creates the `user_login_locations` and `geolocation_ip_blocklist` tables.

---

## Core Security Features

### Proxy/VPN/Tor Detection

Built-in to all providers:

```php
$details = Geolocation::lookup($request->ip());

if ($details->isProxy()) {
    // User is using a proxy or VPN
}

if ($details->isTor()) {
    // User is on Tor network
}
```

### Anomaly Detection

Detect suspicious activity patterns:

```php
use Bkhim\Geolocation\Services\AnomalyDetector;

$detector = new AnomalyDetector();

if ($detector->isAnomalous($request->ip(), Auth::id())) {
    // Flag for review - unusual location pattern
}

// Detailed report
$report = $detector->getAnomalyReport($request->ip(), Auth::id());
/*
[
    'is_anomalous' => true,
    'is_impossible_travel' => true,
    'is_new_country' => false,
    'travel_speed_kmh' => 4500
]
*/
```

### MFA Triggers

Trigger multi-factor authentication:

```php
$user = User::find(Auth::id());

if ($user->requiresMfaDueToLocation($request->ip())) {
    return redirect()->route('mfa.challenge');
}
```

---

## Advanced Security

### Threat Intelligence (AbuseIPDB)

Check IPs against global threat databases:

```php
$service = app(\Bkhim\Geolocation\Services\ThreatIntelligenceService::class);

if ($service->isThreat($request->ip())) {
    // IP has known malicious history
    $details = $service->getThreatDetails($request->ip());
    // $details['abuseConfidenceScore'] etc.
}
```

### IP Blocking

Block repeat offenders:

```php
use Bkhim\Geolocation\Models\IpBlocklist;

// Block an IP
IpBlocklist::block('1.2.3.4', 'Failed login attempts');

// Check if blocked
if (IpBlocklist::isBlocked($request->ip())) {
    abort(403, 'Access denied');
}
```

### Audit Logging

Log all security events:

```php
use Bkhim\Geolocation\Contracts\AuditLoggerInterface;

app(AuditLoggerInterface::class)->log('Login attempt', [
    'user_id' => Auth::id(),
    'ip' => $request->ip(),
    'success' => true,
]);
```

---

## Middleware

### `geo.security` - Blocks threats automatically

```php
// Add to routes
Route::middleware('geo.security')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/checkout', [CheckoutController::class, 'store']);
});
```

### `geo.ratelimit` - Country-based rate limits

```php
// config/geolocation.php
'addons' => [
    'rate_limiting' => [
        'enabled' => true,
        'limits' => [
            'US' => 100,
            'CN' => 10,  // Stricter for high-risk
            '*' => 60,  // Default
        ],
    ],
],

// In routes
Route::middleware('geo.ratelimit')->group(function () {
    // Rate limited routes
});
```

---

## CLI Security Tools

### Security Audit

```bash
php artisan geolocation:audit
```

Output:
```
🔒 Geolocation Security Audit – April 11, 2026

Login Locations:
├─ 47 logins from new countries
├─ 8 VPN/proxy logins detected
└─ 2 Tor exit node logins

Recommendations:
├─ Enable MFA for users logging from high-risk countries
└─ Review logins occurring between midnight and 5AM

Compliance:
├─ IP Masking: 94% of IPs anonymized
└─ Data Retention: 30 days (configured)
```

### Prune Old Data

```bash
# Preview
php artisan geolocation:prune --dry-run

# Delete
php artisan geolocation:prune
```

### Update MaxMind

```bash
php artisan geolocation:update-maxmind
```

---

## Complete Login Flow Example

```php
public function handleLogin(Request $request)
{
    $ip = $request->ip();
    $user = Auth::user();
    
    // 1. Check threat intelligence
    $threatService = app(\Bkhim\Geolocation\Services\ThreatIntelligenceService::class);
    if ($threatService->isThreat($ip)) {
        IpBlocklist::block($ip, 'Threat intelligence match');
        return $this->blockedResponse();
    }
    
    // 2. Check geolocation
    $details = Geolocation::lookup($ip);
    
    // 3. Record login
    $user->recordLoginLocation($ip, $details);
    
    // 4. Check anomalies
    $detector = new AnomalyDetector();
    if ($detector->isAnomalous($ip, $user->id)) {
        // Log and require MFA
        app(\Bkhim\Geolocation\Contracts\AuditLoggerInterface::class)
            ->log('Anomalous login', ['user_id' => $user->id, 'ip' => $ip]);
        return redirect()->route('mfa.challenge');
    }
    
    // 5. Block proxies if configured
    if (config('geolocation.security.block_proxies') && $details->isProxy()) {
        return $this->blockedResponse();
    }
    
    return $this->authenticated($request);
}
```

---

## Configuration Reference

```php
// config/geolocation.php
'security' => [
    'enable_mfa_trigger' => true,
    'enable_blocking' => true,
    'high_risk_threshold' => 70,
    'rules' => [
        'proxy' => 40,
        'tor' => 80,
        'crawler' => 20,
    ],
],

'threat_intelligence' => [
    'enabled' => false,
    'abuseipdb_api_key' => env('ABUSEIPDB_API_KEY'),
    'min_confidence_score' => 50,
],
```