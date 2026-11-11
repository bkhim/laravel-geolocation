# Middleware

The package registers four route middleware aliases automatically. You do not
need to add them to `bootstrap/app.php` or `app/Http/Kernel.php`.

| Alias | Class | Purpose |
|-------|-------|---------|
| `geo.allow` | `GeoMiddleware` | Allow only listed countries/continents |
| `geo.deny` | `GeoMiddleware` | Block listed countries/continents |
| `geo.ratelimit` | `GeoRateLimitMiddleware` | Per-country request throttling |
| `geo.security` | `SecurityCheckMiddleware` | Block blocklisted / threat-flagged IPs |

Each middleware checks its own feature flag at request time, so registering the
alias is free. `geo.allow` / `geo.deny` always apply when attached to a route.

## GeoAllow — allow specific locations

```php
Route::get('/us-only', fn () => 'US and Canada only')
    ->middleware('geo.allow:US,CA');

// Continents work too
Route::get('/na-only', fn () => 'North America only')
    ->middleware('geo.allow:NA');
```

## GeoDeny — block specific locations

```php
Route::get('/admin')->middleware('geo.deny:CN,RU,KP');

Route::middleware('geo.deny:CN,RU,KP,AF,PK')->group(function () {
    // Sensitive routes
});
```

Deny responses are configurable:

```php
'addons' => [
    'middleware' => [
        'response_type' => 'abort',   // abort | json | redirect
        'status_code' => 403,
        'redirect_to' => '/restricted',
    ],
],
```

## GeoRateLimit — country-based throttling

Requires `addons.rate_limiting.enabled=true` (or `GEOLOCATION_RATE_LIMITING_ENABLED=true`).

```php
// Uses the per-country limits from config
Route::post('/api/data')->middleware('geo.ratelimit');

// Optional: override max attempts and decay minutes (60s default)
Route::post('/login')->middleware('geo.ratelimit:10,1');
```

Limits accept either an integer or the documented array shape:

```php
'addons' => [
    'rate_limiting' => [
        'enabled' => env('GEOLOCATION_RATE_LIMITING_ENABLED', false),
        'limits' => [
            'US' => ['requests_per_minute' => 100],
            'CN' => 10,                          // int form works too
            '*' => ['requests_per_minute' => 60], // default
        ],
    ],
],
```

Behaviour:

- The country comes from `Geolocation::lookup($request->ip())`; if that lookup
  throws, the request falls back to the `'*'` limit rather than failing.
- Responses include `X-RateLimit-Limit`, `X-RateLimit-Remaining`,
  `X-RateLimit-Reset`, and `Retry-After` on 429.
- Throttled requests receive `429` JSON: `{"error":"Too Many Attempts","retry_after":N}`.

## GeoSecurity — block threats

Requires `security.enable_blocking=true` for blocklist checks; threat checks
additionally require `threat_intelligence.enabled=true`.

```php
Route::middleware('geo.security')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/checkout', [CheckoutController::class, 'store']);
});
```

It will:

1. Reject IPs present in `geolocation_ip_blocklist` with `403`.
2. Query threat intelligence and audit-log detections.
3. Optionally auto-block when `threat_intelligence.auto_block=true`.

## Advanced: manual registration

Aliases are already registered, but if you disable auto-discovery or need to
override the classes, register them yourself. In `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'geo.allow' => \Bkhim\Geolocation\Addons\Middleware\GeoMiddleware::class,
        'geo.deny' => \Bkhim\Geolocation\Addons\Middleware\GeoMiddleware::class,
        'geo.ratelimit' => \Bkhim\Geolocation\Addons\Middleware\GeoRateLimitMiddleware::class,
        'geo.security' => \Bkhim\Geolocation\Addons\Middleware\SecurityCheckMiddleware::class,
    ]);
})
```

The package supports Laravel 12–13 only.
