# Installation

Install the package via Composer:

```bash
composer require bkhim/laravel-geolocation
```

## Auto-Discovery

The package uses Laravel's auto-discovery to automatically register the service provider and facades. This works with Laravel 10+.

## Manual Registration

If you need to disable auto-discovery or register manually:

### Laravel 10 and earlier

Add to `config/app.php`:

```php
'providers' => [
    Bkhim\Geolocation\GeolocationServiceProvider::class,
],

'aliases' => [
    'Geolocation' => Bkhim\Geolocation\Geolocation::class,
    'IpAnonymizer' => Bkhim\Geolocation\Facades\IpAnonymizer::class,
    'LocationConsentManager' => Bkhim\Geolocation\Facades\LocationConsentManager::class,
    'GeoAnomalyDetector' => Bkhim\Geolocation\Facades\GeoAnomalyDetector::class,
    'ThreatIntelligence' => Bkhim\Geolocation\Facades\ThreatIntelligence::class,
],
```

### Laravel 11+

Add to `bootstrap/providers.php`:

```php
return [
    Bkhim\Geolocation\GeolocationServiceProvider::class,
];
```

## Publish Configuration

Publish the configuration file to customize your settings:

```bash
php artisan vendor:publish --provider="Bkhim\Geolocation\GeolocationServiceProvider"
```

This creates `config/geolocation.php` where you can configure providers, caching, and addons.

## Requirements

- PHP 8.2+
- Laravel 10.x, 11.x, 12.x, or 13.x
- Composer
- For MaxMind: MaxMind GeoLite2 database (free) or GeoIP2 database (paid)

## Next Steps

- [Configuration](configuration.md) - Set up environment variables
- [Quick Start](quick-start.md) - Your first geolocation lookup
