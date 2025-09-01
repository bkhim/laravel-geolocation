<?php

namespace Bkhim\GeoLocation;

use Bkhim\GeoLocation\Console\GeoLocationCommand;
use Illuminate\Support\ServiceProvider;

/**
 * Class GeoLocationServiceProvider.
 *
 * @author Adriano Rosa <https://adrianorosa.com>
 * @date 2019-08-13 10:04
 *
 * @package Bkhim
 */
class GeoLocationServiceProvider extends ServiceProvider
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
            return new GeoLocationManager(
                config('geolocation'),
                $app->get('cache')
            );
        });
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

        // Publish database directory structure for MaxMind
        $this->publishes([
            __DIR__ . '/../database/geoip' => storage_path('app/geoip')
        ], 'geolocation-storage');

        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/../translations', 'geolocation');

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                GeoLocationCommand::class,
                // Add future commands here
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function provides()
    {
        return ['geolocation'];
    }
}
