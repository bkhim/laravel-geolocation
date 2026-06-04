# Events

The package dispatches events for monitoring and integration with your application.

## Available Events

### HighRiskIpDetected

Dispatched when high-risk indicators are detected:

```php
// Event: Bkhim\Geolocation\Events\HighRiskIpDetected
// Properties: $ip, $riskScore, $indicators

use Bkhim\Geolocation\Events\HighRiskIpDetected;
use App\Listeners\LogHighRiskLogin;

protected $listen = [
    HighRiskIpDetected::class => [
        LogHighRiskLogin::class,
    ],
];
```

### SuspiciousLocationDetected

Dispatched when unusual location patterns are detected:

```php
use Bkhim\Geolocation\Events\SuspiciousLocationDetected;

// In a listener
public function handle(SuspiciousLocationDetected $event)
{
    Log::warning('Suspicious login location', [
        'user_id' => $event->user->id,
        'ip' => $event->location->getIp(),
        'country' => $event->location->getCountryCode(),
    ]);
}
```

### GeoBlockedRequest

Dispatched when a request is blocked by geo-middleware:

```php
use Bkhim\Geolocation\Events\GeoBlockedRequest;

// In a listener
public function handle(GeoBlockedRequest $event)
{
    Log::warning('Geo-blocked request', [
        'ip' => $event->request->ip(),
        'country' => $event->countryCode,
    ]);
}
```

### LoginLocationRecorded

Dispatched after a login location is recorded:

```php
use Bkhim\Geolocation\Events\LoginLocationRecorded;

// In your EventServiceProvider
protected $listen = [
    LoginLocationRecorded::class => [
        App\Listeners\NotifySecurityTeam::class,
    ],
];
```

## Registering Listeners

In `app/Providers/EventServiceProvider.php`:

```php
use Bkhim\Geolocation\Events\HighRiskIpDetected;
use Bkhim\Geolocation\Events\SuspiciousLocationDetected;
use Bkhim\Geolocation\Events\GeoBlockedRequest;
use App\Listeners\LogHighRiskLogin;
use App\Listeners\NotifySecurityTeam;
use App\Listeners\LogGeoBlocked;

protected $listen = [
    HighRiskIpDetected::class => [
        LogHighRiskLogin::class,
    ],
    SuspiciousLocationDetected::class => [
        NotifySecurityTeam::class,
    ],
    GeoBlockedRequest::class => [
        LogGeoBlocked::class,
    ],
];
```

## Event Properties

| Event | Properties |
|-------|------------|
| `HighRiskIpDetected` | `$user`, `$loginHistory` |
| `SuspiciousLocationDetected` | `$user`, `$loginHistory` |
| `GeoBlockedRequest` | `$request`, `$countryCode` |
| `LoginLocationRecorded` | `$user`, `$loginHistory` |

## Event Property Details

### HighRiskIpDetected & SuspiciousLocationDetected

Both events have the same properties:

```php
$event->user;           // The user model
$event->loginHistory;   // LoginHistory model with location data
```

Access location data:
```php
$event->loginHistory->ip;
$event->loginHistory->country_code;
$event->loginHistory->city;
$event->loginHistory->is_proxy;
$event->loginHistory->is_tor;
```
