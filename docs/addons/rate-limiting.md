# Country-Based Rate Limiting

Apply different rate limits based on user country/region.

## Overview

The package includes rate limiting middleware that applies different limits based on geographic location. This is useful for applying stricter limits to high-risk regions or regions with known abuse patterns.

## Basic Usage

```php
// Apply rate limit: 60 requests per minute to all users
Route::post('/api/data')->middleware('geo.ratelimit:60,1');

// Apply stricter limit: 10 requests per minute
Route::post('/login')->middleware('geo.ratelimit:10,1');
```

## Configuration

In `config/geolocation.php`:

```php
'addons' => [
    'rate_limiting' => [
        'enabled' => env('GEOLOCATION_RATE_LIMITING_ENABLED', false),
        'limits' => [
            // Array form
            'CN' => ['requests_per_minute' => 5],
            'RU' => ['requests_per_minute' => 3],

            // Integer form is also accepted
            'KP' => 1,

            // Default for anything not listed
            '*' => ['requests_per_minute' => 60],
        ],
    ],
],
```

Enable with `GEOLOCATION_RATE_LIMITING_ENABLED=true`.

## How It Works

1. User makes a request
2. Middleware looks up the user's location (falls back to `'*'` if the lookup fails)
3. It matches the country code, then the `'*'` default
4. The limit is applied per user (id) or IP

## Priority

1. Country code (e.g., `CN`)
2. Default (`*`)

## Response Headers

The headers are added on both allowed and throttled responses:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 42
X-RateLimit-Reset: 1640000000
Retry-After: 45        # only on 429
```

## JSON Response

```json
{
    "error": "Too Many Attempts",
    "retry_after": 45
}
```

The limiter uses Laravel's `RateLimiter` facade, so it is backed by your cache
store. `php artisan cache:clear` resets counters.

## Best Practices

1. **Start permissive** - Start with higher limits and adjust based on observed behavior
2. **Monitor** - Watch for geographic patterns in abuse
3. **Whitelist** - Consider whitelisting known good IPs
4. **Logging** - Log rate limit hits by country for analysis
