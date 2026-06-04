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
            // Specific country limits
            'CN' => ['requests_per_minute' => 5],
            'RU' => ['requests_per_minute' => 3],
            'KP' => ['requests_per_minute' => 1],
            
            // Continent limits
            'EU' => ['requests_per_minute' => 30],
            
            // Default limit
            '*' => ['requests_per_minute' => 60],
        ],
        'message' => 'Too Many Attempts.',
    ],
],
```

## How It Works

1. User makes a request
2. Middleware looks up the user's location
3. It checks for a country-specific limit, then continent, then default
4. Rate limit is applied based on the matched limit

## Priority

1. Country code (e.g., `CN`)
2. Default (`*`)

## Response Headers

When rate limited, these headers are added:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1640000000
Retry-After: 45
```

## JSON Response

```json
{
    "message": "Too Many Attempts.",
    "retry_after": 45
}
```

## Artisan Command

```bash
# No specific command, but works with Laravel's rate limiter
php artisan cache:clear
```

## Best Practices

1. **Start permissive** - Start with higher limits and adjust based on observed behavior
2. **Monitor** - Watch for geographic patterns in abuse
3. **Whitelist** - Consider whitelisting known good IPs
4. **Logging** - Log rate limit hits by country for analysis
