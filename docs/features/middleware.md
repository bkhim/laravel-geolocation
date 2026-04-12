# Middleware

Geo-blocking middleware for access control based on country/continent.

## Available Middleware

### GeoAllow - Allow Specific Locations

```php
// Allow only US and Canada
Route::get('/us-only', function () {
    return 'US and Canada only';
})->middleware('geo.allow:US,CA');

// Allow by continent
Route::get('/na-only', function () {
    return 'North America only'
})->middleware('geo.allow:NA');
```

### GeoDeny - Block Specific Locations

```php
// Block specific countries
Route::get('/admin')->middleware('geo.deny:CN,RU,KP');

// Block entire continents
Route::get('/no-eu')->middleware('geo.deny:EU');

// Block high-risk countries + Tor users
Route::middleware('geo.deny:CN,RU,KP,AF,PK')->group(function () {
    // Sensitive routes
});
```

### GeoRateLimit - Country-Based Rate Limiting

```php
// Default: 60 requests/minute
Route::post('/api/data')->middleware('geo.ratelimit:60,1');

// Stricter limits for high-risk countries
Route::post('/login')->middleware('geo.ratelimit:10,1');
```

## Registration

### Laravel 10 and earlier

In `app/Http/Kernel.php`:

```php
protected $middlewareAliases = [
    'geo.allow' => \Bkhim\Geolocation\Addons\Middleware\GeoMiddleware::class,
    'geo.deny' => \Bkhim\Geolocation\Addons\Middleware\GeoMiddleware::class,
    'geo.ratelimit' => \Bkhim\Geolocation\Addons\Middleware\GeoRateLimitMiddleware::class,
    'geo.security' => \Bkhim\Geolocation\Addons\Middleware\SecurityCheckMiddleware::class,
];
```

### Laravel 11+

In `bootstrap/app.php`:

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

**Note**: When using Laravel 11+, set `GEOLOCATION_MIDDLEWARE_ENABLED=false` in `.env` to prevent duplicate registration.

## Configuration

In `config/geolocation.php`:

```php
'addons' => [
    'middleware' => [
        'enabled' => env('GEOLOCATION_MIDDLEWARE_ENABLED', false),
        'response_type' => 'abort', // 'abort', 'json', 'redirect'
        'status_code' => 403,
        'redirect_to' => '/',
    ],
    
    'rate_limiting' => [
        'enabled' => env('GEOLOCATION_RATE_LIMITING_ENABLED', false),
        'limits' => [
            'CN' => ['requests_per_minute' => 5],
            'RU' => ['requests_per_minute' => 3],
            '*' => ['requests_per_minute' => 60], // default
        ],
        'message' => 'Too Many Attempts.',
    ],
],
```

## Response Types

```php
// JSON response (for APIs)
'response_type' => 'json'
// Returns: {"error": "Access denied from your location", "code": "GEO_BLOCKED"}

# Redirect
'response_type' => 'redirect',
'redirect_to' => '/blocked'

// Abort with status code (default)
'response_type' => 'abort',
'status_code' => 403
```

### GeoSecurity - Threat Blocking

Blocks IPs that are in the blocklist or flagged by threat intelligence.

```php
// Enable in config/geolocation.php
'addons' => [
    'middleware' => [
        'enabled' => true,
    ],
],

// In routes
Route::middleware('geo.security')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/checkout', [CheckoutController::class, 'store']);
});
```

This middleware:
- Checks if IP is in the blocklist (`geolocation_ip_blocklist`)
- Checks AbuseIPDB threat intelligence (if configured)
- Auto-blocks threats if enabled
- Returns 403 with JSON error response
