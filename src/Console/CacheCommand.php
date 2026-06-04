<?php

namespace Bkhim\Geolocation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Class CacheCommand
 *
 * Console command for managing geolocation cache data following Laravel best practices.
 *
 * @package Bkhim\Geolocation\Console
 */
class CacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geolocation:cache
                            {action : Action to perform: clear, info, warm-up, optimize}
                            {--provider= : Specific provider to target (ipinfo, maxmind, etc.)}
                            {--ip= : Specific IP to target for cache operations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage geolocation cache data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'clear':
                return $this->clearCache();

            case 'info':
                return $this->showCacheInfo();

            case 'warm-up':
                return $this->warmUpCache();

            case 'optimize':
                return $this->optimizeCache();

            default:
                $this->error("Unknown action: {$action}");
                $this->info("Available actions: clear, info, warm-up, optimize");
                return 1;
        }
    }

    /**
     * Clear geolocation cache.
     */
    protected function clearCache(): int
    {
        $provider = $this->option('provider');
        $ip = $this->option('ip');

        if ($ip && $provider) {
            // Clear specific IP for specific provider
            return $this->clearSpecificCache($provider, $ip);
        }

        if ($provider) {
            // Clear all cache for specific provider
            return $this->clearProviderCache($provider);
        }

        // Clear all geolocation cache
        return $this->clearAllGeolocationCache();
    }

    /**
     * Clear specific cache entry.
     */
    protected function clearSpecificCache(string $provider, string $ip): int
    {
        $prefix = config('geolocation.cache.prefix', 'geolocation');
        $cacheKey = "{$prefix}:{$provider}:" . md5($ip);

        $this->info("Clearing cache for {$provider} provider and IP {$ip}...");

        try {
            Cache::forget($cacheKey);
            $this->info("Cache cleared successfully");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to clear cache: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Clear cache for specific provider.
     */
    protected function clearProviderCache(string $provider): int
    {
        $this->info("Clearing cache for {$provider} provider...");

        $cacheConfig = config('geolocation.cache');

        if ($cacheConfig['tags']['enabled'] ?? false) {
            $tags = array_merge($cacheConfig['tags']['names'] ?? ['geolocation'], [$provider]);

            try {
                Cache::tags($tags)->flush();
                $this->info("Provider cache cleared successfully using tags");
                return 0;
            } catch (\Exception $e) {
                $this->error("Failed to clear provider cache: " . $e->getMessage());
                return 1;
            }
        }

        $this->warn("Cache tags are not enabled. Cannot clear provider-specific cache safely.");
        $this->info("Consider enabling cache tags in your configuration for better cache management.");
        return 1;
    }

    /**
     * Clear all geolocation cache.
     */
    protected function clearAllGeolocationCache(): int
    {
        $this->info('Clearing all geolocation cache...');

        $cacheConfig = config('geolocation.cache');

        if ($cacheConfig['tags']['enabled'] ?? false) {
            $tags = $cacheConfig['tags']['names'] ?? ['geolocation'];

            try {
                Cache::tags($tags)->flush();
                $this->info('All geolocation cache cleared successfully using tags');
                return 0;
            } catch (\Exception $e) {
                $this->error('Failed to clear cache using tags: ' . $e->getMessage());
                return 1;
            }
        }

        $this->warn('Cache tags are disabled. Cannot safely clear cache without affecting other data.');
        $this->warn('Consider enabling cache tags in your configuration.');

        if ($this->confirm('Do you want to manually clear cache entries? (This will attempt pattern matching)')) {
            return $this->manualCacheClear();
        }

        return 1;
    }

    /**
     * Manually clear cache entries by pattern (fallback method).
     */
    protected function manualCacheClear(): int
    {
        $this->warn('Manual cache clearing is not recommended and may not work with all cache drivers.');
        $this->info('Please consider using Redis or Memcached with cache tags for better cache management.');

        // This is a fallback and won't work with all cache drivers
        return 1;
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
                ['Enabled', $cacheConfig['enabled'] ? 'Yes' : 'No', 'Whether caching is active'],
                ['TTL', $cacheConfig['ttl'] . 's (' . $this->formatDuration($cacheConfig['ttl']) . ')', 'Cache lifetime'],
                ['Store', $cacheStore, 'Cache driver being used'],
                ['Prefix', $cacheConfig['prefix'] ?? 'geolocation', 'Cache key prefix'],
                ['Tags Enabled', ($cacheConfig['tags']['enabled'] ?? false) ? 'Yes' : 'No', 'Cache tags for bulk operations'],
                ['Tag Names', implode(', ', $cacheConfig['tags']['names'] ?? []), 'Active cache tag names'],
            ]
        );

        $this->line('');
        $this->info('Cache Driver Capabilities:');

        // Check cache driver capabilities
        try {
            $cache = Cache::store($cacheConfig['store']);
            $supportsTagging = method_exists($cache, 'tags');

            $this->table(
                ['Feature', 'Supported'],
                [
                    ['Cache Tags', $supportsTagging ? 'Yes' : 'No'],
                    ['Atomic Operations', method_exists($cache, 'lock') ? 'Yes' : 'No'],
                ]
            );
        } catch (\Exception $e) {
            $this->error('Error checking cache driver: ' . $e->getMessage());
        }

        return 0;
    }

    /**
     * Warm up cache with common IP addresses.
     */
    protected function warmUpCache(): int
    {
        $this->info('Warming up geolocation cache...');

        // Common IP addresses to pre-cache
        $commonIps = [
            '8.8.8.8',      // Google DNS
            '1.1.1.1',      // Cloudflare DNS
            '208.67.222.222', // OpenDNS
            '77.88.8.8',    // Yandex DNS
        ];

        $provider = $this->option('provider') ?: config('geolocation.drivers.default');
        $geolocation = app('geolocation')->driver($provider);

        $this->info("Using provider: {$provider}");

        $progressBar = $this->output->createProgressBar(count($commonIps));
        $progressBar->start();

        foreach ($commonIps as $ip) {
            try {
                $geolocation->lookup($ip);
                $progressBar->advance();
            } catch (\Exception $e) {
                $this->line(''); // New line after progress bar
                $this->warn("Failed to warm up cache for {$ip}: " . $e->getMessage());
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->line(''); // New line after progress bar
        $this->info('Cache warm-up completed');

        return 0;
    }

    /**
     * Optimize cache configuration and provide recommendations.
     */
    protected function optimizeCache(): int
    {
        $this->info('Analyzing geolocation cache configuration...');
        $this->line('');

        $cacheConfig = config('geolocation.cache');
        $recommendations = [];

        // Check cache driver
        $cacheStore = $cacheConfig['store'] ?: config('cache.default');
        $cacheDriver = config("cache.stores.{$cacheStore}.driver");

        if (in_array($cacheDriver, ['file', 'database'])) {
            $recommendations[] = [
                'area' => 'Cache Driver',
                'issue' => 'Using ' . $cacheDriver . ' driver',
                'recommendation' => 'Consider using Redis or Memcached for better performance',
                'priority' => 'High'
            ];
        }

        // Check TTL
        $ttl = $cacheConfig['ttl'];
        if ($ttl < 3600) {
            $recommendations[] = [
                'area' => 'TTL',
                'issue' => 'Short TTL (' . $this->formatDuration($ttl) . ')',
                'recommendation' => 'Consider increasing TTL to 1-24 hours for geolocation data',
                'priority' => 'Medium'
            ];
        } elseif ($ttl > 604800) {
            $recommendations[] = [
                'area' => 'TTL',
                'issue' => 'Very long TTL (' . $this->formatDuration($ttl) . ')',
                'recommendation' => 'Consider shorter TTL (1-7 days) for more current data',
                'priority' => 'Low'
            ];
        }

        // Check cache tags
        if (!($cacheConfig['tags']['enabled'] ?? false)) {
            $recommendations[] = [
                'area' => 'Cache Tags',
                'issue' => 'Cache tags disabled',
                'recommendation' => 'Enable cache tags for easier cache management',
                'priority' => 'Medium'
            ];
        }

        // Display recommendations
        if (empty($recommendations)) {
            $this->info('✅ Cache configuration looks optimal!');
        } else {
            $this->warn('Found ' . count($recommendations) . ' optimization opportunities:');
            $this->line('');
            $this->table(
                ['Area', 'Issue', 'Recommendation', 'Priority'],
                $recommendations
            );
        }

        return 0;
    }

    /**
     * Format duration in human-readable format.
     */
    protected function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        } elseif ($seconds < 3600) {
            return round($seconds / 60, 1) . 'm';
        } elseif ($seconds < 86400) {
            return round($seconds / 3600, 1) . 'h';
        } else {
            return round($seconds / 86400, 1) . 'd';
        }
    }
}
