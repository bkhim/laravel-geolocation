# Fallback Configuration

Automatic failover to secondary providers when the primary fails.

## Concept

Configure fallback providers to ensure geolocation works even when your primary provider experiences issues:

```php
// Primary: ipapi (free, default)
// Fallback: IpInfo, then MaxMind
```

## Configuration

In `config/geolocation.php`:

```php
'fallback' => [
    'enabled' => true,
    'order' => ['ipinfo', 'maxmind'],
    'max_attempts' => 2,
],
```

Or partially via environment variables:

```env
GEOLOCATION_FALLBACK_ENABLED=true
GEOLOCATION_FALLBACK_ATTEMPTS=2
```

The order itself is only configurable in `config/geolocation.php`
(`fallback.order`). `max_attempts` counts **fallback** providers after the
primary, so `2` means primary + up to two fallbacks. `0` disables fallbacks.

## How It Works

1. Primary provider is called first
2. If it fails (timeout, error), the next provider in order is tried
3. This continues until a provider succeeds, or `max_attempts` fallbacks have
   failed
4. If all fail, the last exception is thrown

## Example

```php
// With fallback enabled, this tries: ipapi -> ipinfo -> maxmind
$details = Geolocation::lookup('8.8.8.8');
```

## Use Cases

- **High availability** - Ensure geolocation works even during provider outages
- **Rate limit handling** - Fall back when primary rate limit is hit
- **Geographic coverage** - Use different providers for different regions

## Best Practices

1. **Put reliable providers first** - Your primary should be the most reliable
2. **Include MaxMind as last resort** - It uses local DB, can't fail due to API issues
3. **Set max_attempts** - Don't try too many providers (2-3 is usually enough)
4. **Monitor failures** - Use logging to track when fallbacks occur
