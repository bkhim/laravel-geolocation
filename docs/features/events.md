# Events

The package dispatches events for monitoring and integration.

## Available Events

| Event | Properties | Dispatched when |
|-------|------------|-----------------|
| `LoginLocationRecorded` | `$user`, `$loginHistory` | `recordLoginLocation()` stores a login |
| `SuspiciousLocationDetected` | `$user`, `$loginHistory` | A recorded login requires MFA by location |
| `HighRiskIpDetected` | `$user`, `$loginHistory` | A recorded login's risk score is high |
| `GeoBlockedRequest` | `$user`, `$ip` | Class is available for your own geo-block flows |

```php
use Bkhim\Geolocation\Events\HighRiskIpDetected;

public function handle(HighRiskIpDetected $event): void
{
    Log::warning('High-risk login', [
        'user_id' => $event->user->id,
        'ip' => $event->loginHistory->ip,
        'country' => $event->loginHistory->country_code,
        'proxy' => $event->loginHistory->is_proxy,
        'tor' => $event->loginHistory->is_tor,
    ]);
}
```

## Automatic security events

The package registers **one** listener for `LoginLocationRecorded`
(`Bkhim\Geolocation\Listeners\CheckLoginSecurity`) when the service provider
boots. It evaluates the recorded login and dispatches
`SuspiciousLocationDetected` / `HighRiskIpDetected`.

You do not register this listener yourself, and it never fires twice — earlier
versions registered it inside a trait boot method, which duplicated events for
every model using the trait.

Disable the automatic evaluation with:

```env
GEOLOCATION_SECURITY_EVENTS_ENABLED=false
```

## Registering your own listeners

Laravel 11+ — in `AppServiceProvider::boot()`:

```php
use Illuminate\Support\Facades\Event;
use Bkhim\Geolocation\Events\HighRiskIpDetected;
use App\Listeners\LogHighRiskLogin;

public function boot(): void
{
    Event::listen(HighRiskIpDetected::class, LogHighRiskLogin::class);
}
```


## Property reference

```php
// LoginLocationRecorded, SuspiciousLocationDetected, HighRiskIpDetected
$event->user;                         // the user model
$event->loginHistory;                 // LoginHistory model
$event->loginHistory->ip;             // masked IP as stored
$event->loginHistory->country_code;   // e.g. "US"
$event->loginHistory->city;
$event->loginHistory->timezone;
$event->loginHistory->currency_code;
$event->loginHistory->is_proxy;
$event->loginHistory->is_tor;
$event->loginHistory->occurred_at;

// GeoBlockedRequest
$event->user;
$event->ip;
```
