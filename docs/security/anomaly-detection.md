# Anomaly Detection

Detect unusual location patterns that may indicate fraudulent activity using the built-in `AnomalyDetector` service.

## Using the Built-in Service

```php
use Bkhim\Geolocation\Services\AnomalyDetector;

$detector = new AnomalyDetector();

// Check if login is anomalous
if ($detector->isAnomalous($request->ip(), Auth::id())) {
    // Flag for review or require additional verification
}
```

## Quick Checks

### New Country Detection

```php
$detector = new AnomalyDetector();

if ($detector->isNewCountry($request->ip(), Auth::id())) {
    // First login from this country - may require verification
}
```

### New City Detection

```php
if ($detector->isNewCity($request->ip(), Auth::id())) {
    // First login from this city
}
```

### Impossible Travel Detection

```php
$history = $detector->getHistory(Auth::id());
$lastLogin = $history->first();

$current = Geolocation::lookup($request->ip());

if ($lastLogin && $detector->isImpossibleTravel($lastLogin, $current)) {
    // User would need to travel impossibly fast between locations
}
```

## Detailed Anomaly Report

Get a complete analysis:

```php
$report = $detector->getAnomalyReport($request->ip(), Auth::id());

/*
Returns:
[
    'is_anomalous' => true,
    'is_impossible_travel' => true,
    'is_new_country' => false,
    'is_new_city' => true,
    'too_many_countries' => false,
    'country_count' => 2,
    'previous_countries' => ['US', 'CA'],
    'distance_from_last_login' => 4500.5, // km
    'travel_speed_kmh' => 4500.5 // km/h
]
*/
```

## Audit Logging

You can automatically log anomalies using the `AuditLogger` interface. This is crucial for compliance and security auditing:

```php
use Bkhim\Geolocation\Contracts\AuditLoggerInterface;
use Bkhim\Geolocation\Services\AnomalyDetector;

public function handleLogin(Request $request)
{
    $detector = new AnomalyDetector();
    
    if ($detector->isAnomalous($request->ip(), Auth::id())) {
        app(AuditLoggerInterface::class)->log('Suspicious login attempt', [
            'user_id' => Auth::id(),
            'ip' => $request->ip()
        ]);
        
        return redirect()->route('verify.identity');
    }
}
```

## Configuration

The detector has configurable thresholds:

```php
$detector = (new AnomalyDetector())
    ->setMaxSpeed(1000)           // Max km/h (default: 1000)
    ->setMaxCountries(3)          // Max countries in 30 days (default: 3)
    ->setHistoryDays(30);         // Days to analyze (default: 30)
```

## Usage in Login Flow

```php
use Bkhim\Geolocation\Services\AnomalyDetector;
use Bkhim\Geolocation\Events\SuspiciousLocationDetected;

public function handleLogin(Request $request)
{
    $detector = new AnomalyDetector();
    
    if ($detector->isAnomalous($request->ip(), Auth::id())) {
        // Log and dispatch event
        event(new SuspiciousLocationDetected(
            Auth::user(), 
            LoginHistory::latest()->first()
        ));
        
        // Require additional verification
        return redirect()->route('verify.identity');
    }
}
```

## Detection Patterns

| Pattern | Method | Description |
|---------|--------|-------------|
| Impossible Travel | `isImpossibleTravel()` | Login from distant locations in short time |
| New Country | `isNewCountry()` | First login from new country |
| New City | `isNewCity()` | First login from new city |
| Too Many Countries | `hasTooManyCountries()` | Logins from 3+ countries in 30 days |

## Distance Calculation

The service includes a Haversine formula implementation:

```php
$distance = $detector->calculateDistance(
    40.7128, -74.0060,  // New York
    34.0522, -118.2437  // Los Angeles
);
// Returns: ~3940 km
```