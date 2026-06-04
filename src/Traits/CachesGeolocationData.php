<?php

namespace Bkhim\Geolocation\Traits;

use Illuminate\Support\Facades\Cache;

/**
 * Trait CachesGeolocationData
 *
 * Provides consistent caching behavior for all geolocation providers
 * following Laravel cache best practices.
 *
 * @package Bkhim\Geolocation\Traits
 */
trait CachesGeolocationData
{
    /**
     * Get cache key for geolocation data.
     *
     * @param string $provider Provider name (e.g., 'ipinfo', 'maxmind')
     * @param string|null $ipAddress IP address to cache
     * @return string
     */
    protected function getCacheKey(string $provider, ?string $ipAddress = null): string
    {
        $prefix = config('geolocation.cache.prefix', 'geolocation');
        $hash = md5($ipAddress ?? 'current');

        return "{$prefix}:{$provider}:{$hash}";
    }

    /**
     * Get cache TTL in seconds.
     *
     * @return int
     */
    protected function getCacheTtl(): int
    {
        return (int) config('geolocation.cache.ttl', 86400);
    }

    /**
     * Check if caching is enabled.
     *
     * @return bool
     */
    protected function isCacheEnabled(): bool
    {
        return (bool) config('geolocation.cache.enabled', true);
    }

    /**
     * Get cache tags if enabled.
     *
     * @return array|null
     */
    protected function getCacheTags(): ?array
    {
        if (!config('geolocation.cache.tags.enabled', false)) {
            return null;
        }

        return config('geolocation.cache.tags.names', ['geolocation']);
    }

    /**
     * Remember data in cache with optional tags support.
     *
     * @param string $key Cache key
     * @param int $ttl Time to live in seconds
     * @param \Closure $callback Callback to execute if cache miss
     * @return mixed
     */
    protected function cacheRemember(string $key, int $ttl, \Closure $callback)
    {
        $tags = $this->getCacheTags();

        if ($tags && method_exists($this->cache, 'tags')) {
            return $this->cache->tags($tags)->remember($key, $ttl, $callback);
        }

        return $this->cache->remember($key, $ttl, $callback);
    }

    /**
     * Store data in cache with optional tags support.
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $ttl Time to live in seconds
     * @return bool
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
     * @param string $key Cache key to forget
     * @return bool
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
     *
     * @return bool
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
