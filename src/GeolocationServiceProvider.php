<?php

namespace Bkhim\Geolocation;

use Bkhim\Geolocation\Addons\Anonymization\IpAnonymizer;
use Bkhim\Geolocation\Addons\Gdpr\LocationConsentManager;
use Illuminate\Support\ServiceProvider;

/**
 * Class GeolocationServiceProvider.
 *
 * @author Adriano Rosa <https://adrianorosa.com>
 * @date 2019-08-13 10:04
 *
 * @package Bkhim
 */
class GeolocationServiceProvider extends ServiceProvider
{

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/geolocation.php', 'geolocation');

        $this->app->singleton('geolocation', function ($app) {
            $cacheStore = config('geolocation.cache.store');

            try {
                $cacheRepository = $cacheStore ? $app['cache']->store($cacheStore) : $app['cache']->driver();

                // Validate cache store exists and is working
                if ($cacheStore && !$cacheRepository) {
                    throw new \InvalidArgumentException(
                        "Cache store '{$cacheStore}' is not configured. Please check your cache configuration."
                    );
                }

            } catch (\Exception $e) {
                // Log cache configuration issue but don't fail the service
                if ($app->bound('log')) {
                    $app['log']->warning("Geolocation cache configuration issue: " . $e->getMessage());
                }

                // Fallback to default cache driver
                $cacheRepository = $app['cache']->driver();
            }

            return new \Bkhim\Geolocation\GeolocationManager(
                config('geolocation'),
                $cacheRepository
            );
        });

        // Register addons if enabled
        $this->registerAddons();
    }

    public function boot()
    {
        // Validate configuration
        $this->validateConfiguration();

        // Publish config file
        $this->publishes([
            __DIR__ . '/../config/geolocation.php' => config_path('geolocation.php'),
        ], 'geolocation-config');

        // Publish translations
        $this->publishes([
            __DIR__ . '/../translations' => resource_path('lang/vendor/geolocation')
        ], 'geolocation-translations');

        // Create and publish storage directory for MaxMind databases
        $this->publishes([], 'geolocation-storage');

        // Create the directory if it doesn't exist
        $this->ensureStorageDirectoryExists();

        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/../translations', 'geolocation');

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Bkhim\Geolocation\Console\GeolocationCommand::class,
                \Bkhim\Geolocation\Console\CacheCommand::class,
            ]);
        }

        $this->bootAddons();
    }

    /**
     * Validate package configuration.
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function validateConfiguration(): void
    {
        $driver = config('geolocation.drivers.default');
        $providers = config('geolocation.providers', []);

        // Validate default driver is configured
        if (!array_key_exists($driver, $providers)) {
            throw new \InvalidArgumentException(
                "Default geolocation driver '{$driver}' is not configured in config('geolocation.providers')"
            );
        }

        // Validate required API keys based on driver
        switch ($driver) {
            case 'ipinfo':
                if (empty(config('geolocation.providers.ipinfo.access_token'))) {
                    throw new \InvalidArgumentException(
                        "IpInfo driver is selected but 'GEOLOCATION_IPINFO_ACCESS_TOKEN' is not set in environment"
                    );
                }
                break;
            case 'ipstack':
                if (empty(config('geolocation.providers.ipstack.access_key'))) {
                    throw new \InvalidArgumentException(
                        "IPStack driver is selected but 'GEOLOCATION_IPSTACK_ACCESS_KEY' is not set in environment"
                    );
                }
                break;
            case 'ipgeolocation':
                if (empty(config('geolocation.providers.ipgeolocation.api_key'))) {
                    throw new \InvalidArgumentException(
                        "IPGeolocation driver is selected but 'GEOLOCATION_IPGEOLOCATION_API_KEY' is not set in environment"
                    );
                }
                break;
            case 'maxmind':
                $dbPath = config('geolocation.providers.maxmind.database_path');
                if (empty($dbPath)) {
                    throw new \InvalidArgumentException(
                        "MaxMind driver is selected but 'MAXMIND_DATABASE_PATH' is not configured"
                    );
                }
                break;
            // ipapi doesn't require API key
        }
    }

    protected function ensureStorageDirectoryExists(): void
    {
        $storagePath = storage_path('app/geoip');

        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function provides()
    {
        return ['geolocation'];
    }

    protected function registerAddons(): void
    {
        // Anonymization
        if (config('geolocation.addons.anonymization.enabled')) {
            $this->app->singleton(IpAnonymizer::class, function ($app) {
                return new IpAnonymizer();
            });
        }

        // GDPR
        if (config('geolocation.addons.gdpr.enabled')) {
            $this->app->singleton(LocationConsentManager::class, function ($app) {
                return new LocationConsentManager();
            });
        }
    }

    protected function bootAddons(): void
    {
        // Register middleware
        if (config('geolocation.addons.middleware.enabled')) {
            $router = $this->app['router'];
            $router->aliasMiddleware('geo.allow', \Bkhim\Geolocation\Addons\Middleware\GeoMiddleware::class);
            $router->aliasMiddleware('geo.deny', \Bkhim\Geolocation\Addons\Middleware\GeoMiddleware::class);
            $router->aliasMiddleware('geo.ratelimit', \Bkhim\Geolocation\Addons\Middleware\RateLimitByGeo::class);
        }
    }
}
