# User Traits

Three traits add geolocation, security and personalization to your `User` model.

```php
use Bkhim\Geolocation\Traits\HasGeolocation;
use Bkhim\Geolocation\Traits\HasGeolocationPreferences;
use Bkhim\Geolocation\Traits\HasGeolocationSecurity;

class User extends Authenticatable
{
    use HasGeolocation, HasGeolocationSecurity, HasGeolocationPreferences;
}
```

Publish and run the migration first:

```bash
php artisan vendor:publish --tag=geolocation-migrations
php artisan migrate
```

## HasGeolocation

Records login locations and answers history questions.

| Method | Returns | Notes |
|--------|---------|-------|
| `recordLoginLocation(string $ip)` | `void` | Stores a row and fires `LoginLocationRecorded` |
| `getLastLogin()` | `?LoginHistory` | Latest record |
| `getLastLoginCountry()` | `?string` | ISO country code |
| `isLoginFromNewCountry(string $ip)` | `bool` | Compares against history |
| `isLoginFromNewCity(string $ip)` | `bool` | Compares against history |
| `loginHistories()` | `HasMany` | Relation to the configured model |

```php
$user->recordLoginLocation($request->ip());

if ($user->isLoginFromNewCountry($request->ip())) {
    // First login from this country
}
```

Stored IP handling is controlled by:

```php
'user_trait' => [
    'login_history_table' => 'user_login_locations',
    'login_history_model' => \Bkhim\Geolocation\Models\LoginHistory::class,
    'user_model' => null,                       // defaults to auth.providers.users.model
    'anonymization_mode' => 'partial',          // none | partial | full
    'store_ip' => true,                         // false = do not store an IP at all
    'login_history_retention_days' => 30,
],
```

`partial` masks IPv4 to /24 and IPv6 using the configured
`addons.anonymization.ipv6_mask` (default /64). `full` stores no IP.
The `ip_hash` column is populated when an IP is stored and the mode is not `none`.

## HasGeolocationSecurity

Turns location data into risk decisions.

| Method | Returns | Notes |
|--------|---------|-------|
| `getRiskScore(?string $ip)` | `array` | `score`, `is_high_risk`, `threshold`, `triggers`, `trusted_country` |
| `isHighRiskLogin(?string $ip)` | `bool` | `score >= security.high_risk_threshold` |
| `getLastLoginRiskLevel(?string $ip)` | `string` | `low`, `high`, `critical` |
| `requiresMfaDueToLocation(?string $ip)` | `bool` | Compares level against `security.risk_threshold` |
| `getSuspiciousLoginCount()` | `int` | Proxy/Tor logins in the last 30 days |

```php
$risk = $user->getRiskScore($request->ip());
// ['score' => 45, 'is_high_risk' => false, 'threshold' => 70, ...]

if ($user->requiresMfaDueToLocation($request->ip())) {
    return redirect()->route('mfa.challenge');
}
```

Scoring is configured in `config/geolocation.php`:

```php
'security' => [
    'rules' => [
        'proxy' => 40,
        'tor' => 80,
        'crawler' => 20,
        'new_country' => 30,
        'new_city' => 15,
    ],
    'high_risk_threshold' => 70,
    'risk_threshold' => 'high',
    'trusted_countries' => [],
    'custom_rules' => [],   // classes with a score($user, $details) method
],
```

Security events are dispatched automatically for recorded logins by the
package's listener (`LoginLocationRecorded` → `SuspiciousLocationDetected` /
`HighRiskIpDetected`). Disable with `security.events_enabled=false`.

## HasGeolocationPreferences

Personalization helpers.

```php
$user->getDetectedTimezone();   // e.g. "America/Los_Angeles"
$user->getLocalCurrency();      // e.g. "USD"

$user->getTimezone();           // detected or config('app.timezone')
$user->getCurrency('EUR');      // detected or the supplied default
```

Disable with `personalization.enable_timezone` / `personalization.enable_currency`.

## Connection / network data

Connection characteristics live on `GeolocationDetails`, not the traits:

```php
$details = Geolocation::lookup($request->ip());

$details->getConnectionType(); // residential, corporate, datacenter, dialup, satellite, broadband
$details->getIsp();
$details->getOrg();
$details->getAsn();            // AS15169
$details->getAsnName();
$details->getHostname();
$details->isMobile();
$details->isProxy();
$details->isTor();
$details->isCrawler();
```
