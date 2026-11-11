<?php

namespace Bkhim\Geolocation\Console;

use Bkhim\Geolocation\GeolocationManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Console command for managing geolocation cache data.
 */
class CacheCommand extends Command
{
    protected $signature = 'geolocation:cache
                            {action : Action to perform: clear, info, warm-up, optimize}
                            {--provider= : Specific provider to target (ipinfo, maxmind, etc.)}
                            {--ip= : Specific IP to target for cache operations}';

    protected $description = 'Manage geolocation cache data';

    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'clear' => $this->clearCache(),
            'info' => $this->showCacheInfo(),
            'warm-up' => $this->warmUpCache(),
            'optimize' => $this->optimizeCache(),
            default => $this->unknownAction($action),
        };
    }

    protected function unknownAction(string $action): int
    {
        $this->error("Unknown action: {$action}");
        $this->info('Available actions: clear, info, warm-up, optimize');

        return self::FAILURE;
    }

    /**
     * Resolve the cache repository configured for geolocation.
     */
    protected function cacheStore()
    {
        $store = config('geolocation.cache.store');

        return $store ? Cache::store($store) : Cache::store();
    }

    /**
     * Clear geolocation cache.
     */
    protected function clearCache(): int
    {
        $provider = $this->option('provider');
        $ip = $this->option('ip');

        if ($ip) {
            return $this->clearSpecificCache($provider, $ip);
        }

        if ($provider) {
            return $this->clearProviderCache($provider);
        }

        return $this->clearAllGeolocationCache();
    }

    /**
     * Clear a single cache entry using the same key builder as the package.
     */
    protected function clearSpecificCache(?string $provider, string $ip): int
    {
        $manager = app('geolocation');
        $provider = $provider ?: config('geolocation.drivers.default');

        $this->info("Clearing cache for provider [{$provider}] and IP [{$ip}]...");

        try {
            $key = $manager instanceof GeolocationManager
                ? $manager->getCacheKey($ip, $provider)
                : '';

            $cleared = $key !== '' && $this->cacheStore()->forget($key);

            $cleared
                ? $this->info('Cache entry cleared.')
                : $this->warn('No matching cache entry found (it may already be expired).');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed to clear cache: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Clear cache for a specific provider (requires tags).
     */
    protected function clearProviderCache(string $provider): int
    {
        $this->info("Clearing cache for {$provider} provider...");

        $store = $this->cacheStore();

        if (! method_exists($store, 'tags')) {
            $this->warn('The configured cache store does not support tags.');
            $this->info('Use --ip= to clear individual entries instead.');

            return self::FAILURE;
        }

        try {
            $store->tags($this->tagNames())->flush();
            $this->info('Provider cache cleared successfully.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed to clear provider cache: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Clear all geolocation cache entries.
     */
    protected function clearAllGeolocationCache(): int
    {
        $this->info('Clearing all geolocation cache...');

        $store = $this->cacheStore();

        if (! method_exists($store, 'tags')) {
            $this->warn('The configured cache store does not support tags, so a safe bulk clear is not possible.');
            $this->info('Clear individual entries with: php artisan geolocation:cache clear --ip=8.8.8.8');

            return self::FAILURE;
        }

        try {
            $store->tags($this->tagNames())->flush();
            $this->info('All geolocation cache cleared.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed to clear cache using tags: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * @return array<int, string>
     */
    protected function tagNames(): array
    {
        return config('geolocation.cache.tags.names', ['geolocation']);
    }

    /**
     * Show cache information.
     */
    protected function showCacheInfo(): int
    {
        $this->info('Geolocation Cache Information:');
        $this->line('');

        $cacheConfig = config('geolocation.cache');
        $defaultCacheStore = config('cache.default');
        $cacheStore = $cacheConfig['store'] ?: $defaultCacheStore;

        $this->table(
            ['Setting', 'Value', 'Description'],
            [
                ['Enabled', ($cacheConfig['enabled'] ?? true) ? 'Yes' : 'No', 'Whether caching is active'],
                ['TTL', ($cacheConfig['ttl'] ?? 0).'s ('.$this->formatDuration((int) ($cacheConfig['ttl'] ?? 0)).')', 'Cache lifetime'],
                ['Sliding TTL', ($cacheConfig['sliding'] ?? true) ? 'Yes' : 'No', 'Extend TTL on each hit'],
                ['Store', $cacheStore, 'Cache driver being used'],
                ['Prefix', $cacheConfig['prefix'] ?? 'geolocation', 'Cache key prefix'],
                ['Tags Enabled', ($cacheConfig['tags']['enabled'] ?? false) ? 'Yes' : 'No', 'Cache tags for bulk operations'],
                ['Tag Names', implode(', ', $cacheConfig['tags']['names'] ?? []), 'Active cache tag names'],
            ]
        );

        $this->line('');
        $this->info('Cache Driver Capabilities:');

        try {
            $cache = $this->cacheStore();

            $this->table(
                ['Feature', 'Supported'],
                [
                    ['Cache Tags', method_exists($cache, 'tags') ? 'Yes' : 'No'],
                    ['Sliding TTL (touch)', method_exists($cache, 'touch') ? 'Yes' : 'No'],
                    ['Atomic Operations', method_exists($cache, 'lock') ? 'Yes' : 'No'],
                ]
            );
        } catch (\Throwable $e) {
            $this->error('Error checking cache driver: '.$e->getMessage());
        }

        return self::SUCCESS;
    }

    /**
     * Warm up the cache with common IP addresses for every configured provider.
     */
    protected function warmUpCache(): int
    {
        $provider = $this->option('provider');

        $providers = $provider
            ? [$provider]
            : array_keys(config('geolocation.providers', []));

        if (empty($providers)) {
            $this->error('No providers configured.');

            return self::FAILURE;
        }

        $ips = [
            '8.8.8.8',        // Google DNS
            '1.1.1.1',        // Cloudflare DNS
            '208.67.222.222', // OpenDNS
            '77.88.8.8',      // Yandex DNS
        ];

        foreach ($providers as $name) {
            $this->info("Warming up provider [{$name}]...");

            try {
                $driver = app('geolocation')->driver($name);
            } catch (\Throwable $e) {
                $this->warn("  skipped: {$e->getMessage()}");

                continue;
            }

            $bar = $this->output->createProgressBar(count($ips));
            $bar->start();

            foreach ($ips as $ip) {
                try {
                    $driver->lookup($ip);
                } catch (\Throwable $e) {
                    // Ignore individual failures; the summary reports counts.
                }

                $bar->advance();
            }

            $bar->finish();
            $this->line('');
        }

        $this->info('Cache warm-up completed.');

        return self::SUCCESS;
    }

    /**
     * Analyse the cache configuration and suggest improvements.
     */
    protected function optimizeCache(): int
    {
        $this->info('Analyzing geolocation cache configuration...');
        $this->line('');

        $cacheConfig = config('geolocation.cache');
        $recommendations = [];

        $cacheStore = $cacheConfig['store'] ?: config('cache.default');
        $cacheDriver = config("cache.stores.{$cacheStore}.driver");

        if (in_array($cacheDriver, ['file', 'database'], true)) {
            $recommendations[] = ['Cache Driver', "Using {$cacheDriver} driver", 'Consider Redis or Memcached for better performance', 'High'];
        }

        $ttl = (int) ($cacheConfig['ttl'] ?? 0);

        if ($ttl < 3600) {
            $recommendations[] = ['TTL', 'Short TTL ('.$this->formatDuration($ttl).')', 'Increase to 1-24 hours for geolocation data', 'Medium'];
        } elseif ($ttl > 604800) {
            $recommendations[] = ['TTL', 'Very long TTL ('.$this->formatDuration($ttl).')', 'Consider 1-7 days for fresher data', 'Low'];
        }

        if (! ($cacheConfig['tags']['enabled'] ?? false)) {
            $recommendations[] = ['Cache Tags', 'Cache tags disabled', 'Enable tags for easier bulk invalidation', 'Medium'];
        }

        if (! ($cacheConfig['sliding'] ?? true)) {
            $recommendations[] = ['Sliding TTL', 'Sliding TTL disabled', 'Enable geolocation.cache.sliding to keep hot IPs warm', 'Low'];
        }

        if (empty($recommendations)) {
            $this->info('Cache configuration looks optimal.');

            return self::SUCCESS;
        }

        $this->warn('Found '.count($recommendations).' optimization opportunities:');
        $this->line('');
        $this->table(['Area', 'Issue', 'Recommendation', 'Priority'], $recommendations);

        return self::SUCCESS;
    }

    /**
     * Format a duration in seconds for humans.
     */
    protected function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        }

        if ($seconds < 3600) {
            return round($seconds / 60, 1).'m';
        }

        if ($seconds < 86400) {
            return round($seconds / 3600, 1).'h';
        }

        return round($seconds / 86400, 1).'d';
    }
}
