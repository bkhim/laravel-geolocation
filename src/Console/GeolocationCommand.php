<?php

namespace Bkhim\Geolocation\Console;

use Bkhim\Geolocation\Geolocation;
use Illuminate\Console\Command;

/**
 * Class GeolocationCommand.
 *
 * @author Brian Kimathi <https://briankimathi.com>
 *
 * @package Bkhim\Geolocation\Console
 */
class GeolocationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geolocation:lookup
                            {--ip= : The IP Address to get the details for}
                            {--no-cache : Skip cache and fetch fresh data}
                            {--clear-cache : Clear geolocation cache before lookup}
                            {--show-cache-info : Show cache configuration info}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lookup geolocation data for an IP address with cache management options';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Show cache info if requested
        if ($this->option('show-cache-info')) {
            $this->showCacheInfo();
            return;
        }

        // Clear cache if requested
        if ($this->option('clear-cache')) {
            $this->clearGeolocationCache();
        }

        $ipAddress = $this->option('ip');

        $this->info("Looking up geolocation data for IP: " . ($ipAddress ?: 'current IP'));

        $startTime = microtime(true);

        try {
            // Temporarily disable cache if --no-cache flag is used
            if ($this->option('no-cache')) {
                config(['geolocation.cache.enabled' => false]);
                $this->warn('Cache disabled for this lookup');
            }

            $data = Geolocation::lookup($ipAddress);

            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);

            $this->info("Lookup completed in {$duration}ms");

            $tableData = array_merge(['ip' => $data->getIp()], $data->toArray());

            $this->table(array_keys($tableData), [array_values($tableData)]);

            // Show cache status
            $cacheEnabled = config('geolocation.cache.enabled', true);
            $this->info("Cache status: " . ($cacheEnabled ? 'enabled' : 'disabled'));

        } catch (\Exception $e) {
            $this->error("Geolocation lookup failed: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Show cache configuration information.
     */
    protected function showCacheInfo(): void
    {
        $this->info('Geolocation Cache Configuration:');
        $this->line('');

        $cacheConfig = config('geolocation.cache');

        $this->table(
            ['Setting', 'Value'],
            [
                ['Enabled', $cacheConfig['enabled'] ? 'Yes' : 'No'],
                ['TTL', $cacheConfig['ttl'] . ' seconds (' . gmdate('H:i:s', $cacheConfig['ttl']) . ')'],
                ['Store', $cacheConfig['store'] ?: 'Default (' . config('cache.default') . ')'],
                ['Prefix', $cacheConfig['prefix'] ?? 'geolocation'],
                ['Tags Enabled', ($cacheConfig['tags']['enabled'] ?? false) ? 'Yes' : 'No'],
                ['Tag Names', implode(', ', $cacheConfig['tags']['names'] ?? [])],
            ]
        );
    }

    /**
     * Clear geolocation cache.
     */
    protected function clearGeolocationCache(): void
    {
        $this->info('Clearing geolocation cache...');

        $cacheConfig = config('geolocation.cache');

        if ($cacheConfig['tags']['enabled'] ?? false) {
            $tags = $cacheConfig['tags']['names'] ?? ['geolocation'];

            try {
                \Illuminate\Support\Facades\Cache::tags($tags)->flush();
                $this->info('Tagged cache cleared successfully');
            } catch (\Exception $e) {
                $this->error('Failed to clear tagged cache: ' . $e->getMessage());
            }
        } else {
            $this->warn('Cache tags are disabled. Cannot safely clear cache without affecting other data.');
            $this->warn('Consider enabling cache tags in your configuration for better cache management.');
        }
    }
}
