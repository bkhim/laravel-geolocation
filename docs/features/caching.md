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
    'ttl' => 86400, // 24 hours in seconds
    'store' => null, // Uses default cache store
],
```

Or via environment variables:

```env
GEOLOCATION_CACHE_ENABLED=true
GEOLOCATION_CACHE_TTL=86400
```

## Cache Key Format

Cache keys are automatically generated based on IP address and driver:

```
geolocation:{driver}:{md5-ip}
geolocation:ipapi:a1b2c3d4e5f6...
geolocation:maxmind:a1b2c3d4e5f6...
```

## Programmatic Cache Operations

```php
use Bkhim\Geolocation\Facades\Geolocation;

// Clear all geolocation cache
Geolocation::clearCache();

// Clear specific IP cache
Geolocation::clearCache('8.8.8.8');

// Clear specific provider cache
Geolocation::clearCache(null, 'ipapi');

// Clear specific IP for specific provider
Geolocation::clearCache('8.8.8.8', 'ipapi');

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
5. **Enable cache tags** - For easier cache management (requires Redis/Memcached)

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
    'tags' => [
        'enabled' => true,
        'names' => ['geolocation', 'ip-lookup'],
    ],
],
```
