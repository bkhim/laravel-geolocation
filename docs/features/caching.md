# Caching

High-performance caching to reduce API calls and improve response times.

## How It Works

The package automatically caches geolocation results to reduce API calls:

```php
// First call - hits the API
$details = Geolocation::lookup('8.8.8.8'); // API call

// Second call - from cache
$details = Geolocation::lookup('8.8.8.8'); // Instant (cached)
```

## Configuration

In `config/geolocation.php`:

```php
'cache' => [
    'enabled' => true,
    'ttl' => 86400,          // 24 hours
    'sliding' => true,       // extend TTL on every hit (Laravel 13+)
    'store' => null,         // null = default cache store
    'prefix' => 'geolocation',
    'tags' => [
        'enabled' => false,  // required for bulk clearing
        'names' => ['geolocation', 'ip-lookup'],
    ],
],
```

Or via environment variables:

```env
GEOLOCATION_CACHE_ENABLED=true
GEOLOCATION_CACHE_TTL=86400
GEOLOCATION_CACHE_SLIDING=true
GEOLOCATION_CACHE_PREFIX=geolocation
GEOLOCATION_CACHE_STORE=
GEOLOCATION_CACHE_TAGS_ENABLED=false
```

## Sliding TTL

With `sliding` enabled, a cache hit extends the entry's TTL, so frequently
looked-up IPs stay warm while idle entries still expire:

```php
config(['geolocation.cache.sliding' => true]);
```

It uses `Cache::touch()` and therefore requires **Laravel 13+**. On Laravel 12
the option is ignored and the fixed TTL applies — no errors.

## Cache Key Format

Cache keys are automatically generated based on IP address and driver:

```
{prefix}:{provider}:{md5-ip}
geolocation:ipapi:a1b2c3d4e5f6...
geolocation:maxmind:a1b2c3d4e5f6...
```

`prefix` is configurable (`GEOLOCATION_CACHE_PREFIX`). Providers and the
invalidation API share one key builder, so `clearCache()` always targets the
entry that was written.

## Programmatic Cache Operations

```php
use Bkhim\Geolocation\Facades\Geolocation;

// Clear all geolocation cache
Geolocation::clearCache();

// Clear specific IP cache
Geolocation::clearCache('8.8.8.8');

// Clear specific provider cache
Geolocation::clearCache(null, 'ipapi');

// Clear specific IP for specific provider (works without tags)
Geolocation::clearCache('8.8.8.8', 'ipapi');

// Note: clearing every entry requires cache tags + a taggable store,
// otherwise clearCache() returns false rather than flushing shared cache.

// Get cache key for an IP
$key = Geolocation::getCacheKey('8.8.8.8');
// Returns: "geolocation:ipapi:a1b2c3d4e5f6..."

// Get cache key for specific provider
$key = Geolocation::getCacheKey('8.8.8.8', 'maxmind');
// Returns: "geolocation:maxmind:a1b2c3d4e5f6..."
```

## Console Commands

```bash
# Clear all geolocation cache
php artisan geolocation:cache clear

# Clear cache for specific provider
php artisan geolocation:cache clear --provider=ipapi

# Clear specific IP cache for a provider
php artisan geolocation:cache clear --provider=ipapi --ip=8.8.8.8

# Show cache information
php artisan geolocation:cache info

# Warm up cache with common IPs
php artisan geolocation:cache warm-up

# Analyze and optimize cache
php artisan geolocation:cache optimize
```

## Best Practices

1. **Use caching in production** - Always enable for production apps
2. **Set appropriate TTL** - 24 hours is usually good for geolocation
3. **Use Redis for high traffic** - Configure Redis cache store
4. **Clear cache after provider changes** - When switching providers
5. **Enable cache tags** - Required for safe bulk clearing (Redis/Memcached)
6. **Enable sliding TTL on Laravel 13+** - Keeps hot IPs warm without raising TTL for idle ones

## Example: Redis Configuration

```php
// config/cache.php
'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
    ],
],

// config/geolocation.php
'cache' => [
    'enabled' => true,
    'ttl' => 86400,
    'store' => 'redis', // Use Redis for better performance
    'sliding' => true,
    'tags' => [
        'enabled' => true,
        'names' => ['geolocation', 'ip-lookup'],
    ],
],
```
