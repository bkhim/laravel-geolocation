<?php

namespace Bkhim\Geolocation\Traits;

use Illuminate\Support\Facades\Cache;

/**
 * Trait CachesGeolocationData
 *
 * Provides consistent caching behavior for all geolocation providers
 * following Laravel cache best practices.
 */
trait CachesGeolocationData
{
    /**
     * Get cache key for geolocation data.
     *
     * @param  string  $provider  Provider name (e.g., 'ipinfo', 'maxmind')
     * @param  string|null  $ipAddress  IP address to cache
     */
    protected function getCacheKey(string $provider, ?string $ipAddress = null): string
    {
        $prefix = config('geolocation.cache.prefix', 'geolocation');
        $hash = md5($ipAddress ?? 'current');

        return "{$prefix}:{$provider}:{$hash}";
    }

    /**
     * Get cache TTL in seconds.
     */
    protected function getCacheTtl(): int
    {
        return (int) config('geolocation.cache.ttl', 86400);
    }

    /**
     * Check if caching is enabled.
     */
    protected function isCacheEnabled(): bool
    {
        return (bool) config('geolocation.cache.enabled', true);
    }

    /**
     * Get cache tags if enabled.
     */
    protected function getCacheTags(): ?array
    {
        if (! config('geolocation.cache.tags.enabled', false)) {
            return null;
        }

        return config('geolocation.cache.tags.names', ['geolocation']);
    }

    /**
     * Remember data in cache with optional tags and sliding TTL support.
     *
     * When `geolocation.cache.sliding` is enabled and the cache store supports
     * it, the item's TTL is extended on every access so frequently used IPs
     * never expire while idle ones still age out. Requires Laravel 13+
     * (Cache::touch()); older versions silently fall back to fixed TTL.
     *
     * @param  string  $key  Cache key
     * @param  int  $ttl  Time to live in seconds
     * @param  \Closure  $callback  Callback to execute if cache miss
     * @return mixed
     */
    protected function cacheRemember(string $key, int $ttl, \Closure $callback)
    {
        $repository = $this->resolveCacheRepository();

        $value = $repository->remember($key, $ttl, $callback);

        $this->slideCacheTtl($repository, $key, $ttl);

        return $value;
    }

    /**
     * Resolve the cache repository, applying tags when configured.
     *
     * @return \Illuminate\Contracts\Cache\Repository
     */
    protected function resolveCacheRepository()
    {
        $tags = $this->getCacheTags();

        if ($tags && method_exists($this->cache, 'tags')) {
            return $this->cache->tags($tags);
        }

        return $this->cache;
    }

    /**
     * Extend the cached item's TTL (sliding expiration) when supported.
     *
     * Best-effort: never let a store without touch support (or a failing
     * touch) break a geolocation lookup.
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $repository
     * @param  string  $key
     * @param  int  $ttl
     */
    protected function slideCacheTtl($repository, string $key, int $ttl): void
    {
        if (! config('geolocation.cache.sliding', true)) {
            return;
        }

        if (! method_exists($repository, 'touch')) {
            return;
        }

        try {
            $repository->touch($key, $ttl);
        } catch (\Throwable $e) {
            // Sliding TTL is an optimization; ignore failures.
        }
    }

    /**
     * Store data in cache with optional tags support.
     *
     * @param  string  $key  Cache key
     * @param  mixed  $value  Value to cache
     * @param  int  $ttl  Time to live in seconds
     */
    protected function cachePut(string $key, $value, int $ttl): bool
    {
        $tags = $this->getCacheTags();

        if ($tags && method_exists($this->cache, 'tags')) {
            return $this->cache->tags($tags)->put($key, $value, $ttl);
        }

        return $this->cache->put($key, $value, $ttl);
    }

    /**
     * Forget cached data with optional tags support.
     *
     * @param  string  $key  Cache key to forget
     */
    protected function cacheForget(string $key): bool
    {
        $tags = $this->getCacheTags();

        if ($tags && method_exists($this->cache, 'tags')) {
            return $this->cache->tags($tags)->forget($key);
        }

        return $this->cache->forget($key);
    }

    /**
     * Flush all geolocation cache data.
     * If tags are enabled, only tagged data will be flushed.
     * Otherwise, this method will not flush anything to prevent
     * accidentally clearing all application cache.
     */
    public function flushGeolocationCache(): bool
    {
        $tags = $this->getCacheTags();

        if ($tags && method_exists($this->cache, 'tags')) {
            return $this->cache->tags($tags)->flush();
        }

        // Don't flush entire cache without tags as it's too dangerous
        return false;
    }
}
