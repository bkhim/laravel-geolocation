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
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/geolocation.php', 'geolocation');

        $this->app->singleton('geolocation', function ($app) {
            return new \Bkhim\Geolocation\GeolocationManager(
                config('geolocation'),
                $app->get('cache')
            );
        });

        // Register addons if enabled
        $this->registerAddons();
    }

    public function boot()
    {
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
            ]);
        }

        $this->bootAddons();
    }

    protected function ensureStorageDirectoryExists()
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

    protected function registerAddons()
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

    protected function bootAddons()
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
