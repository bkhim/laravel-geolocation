# Laravel Geolocation Package

A modern, feature-rich geolocation package for Laravel with multiple driver support. Originally forked from [adrianorsouza/laravel-geolocation](https://github.com/adrianorsouza/laravel-geolocation), now significantly enhanced and maintained.

## Features

- **Multiple Drivers**: Support for IpInfo API and MaxMind database
- **Laravel 12+ Ready**: Full compatibility with latest Laravel versions
- **Enhanced Caching**: Intelligent caching system with configurable TTL
- **Robust Error Handling**: Comprehensive exception handling and validation
- **IP Validation**: Built-in IP address validation
- **Translation Support**: Multi-language country name translations
- **Artisan Commands**: Built-in CLI tools for testing and verification

## Installation

```bash
composer require bkhim/laravel-geolocation
```

## Supported Laravel Versions

- Laravel 5.7+ to 12.x
- PHP 8.2+

## Quick Start

### Using IpInfo (API)

1. Get an API token from [ipinfo.io](https://ipinfo.io/account/token)
2. Configure your `.env`:
```env
GEOLOCATION_DRIVER=ipinfo
GEOLOCATION_IPINFO_ACCESS_TOKEN=your_token_here
```

### Using MaxMind (Local Database)

1. Download GeoLite2 City database from [MaxMind](https://dev.maxmind.com/geoip/geolite2-free-geolocation-data)
2. Configure your `.env`:
```env
GEOLOCATION_DRIVER=maxmind
MAXMIND_DATABASE_PATH=/path/to/GeoLite2-City.mmdb
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=geolocation-config
```

### Environment Variables

```env
# Default driver (ipinfo or maxmind)
GEOLOCATION_DRIVER=ipinfo

# IpInfo Configuration
GEOLOCATION_IPINFO_ACCESS_TOKEN=your_ipinfo_token

# MaxMind Configuration
MAXMIND_DATABASE_PATH=/path/to/GeoLite2-City.mmdb
MAXMIND_LICENSE_KEY=your_license_key

# Cache Settings
GEOLOCATION_CACHE_ENABLED=true
GEOLOCATION_CACHE_TTL=86400

# Request Settings
GEOLOCATION_TIMEOUT=5
GEOLOCATION_RETRY_ATTEMPTS=2
GEOLOCATION_RETRY_DELAY=100
```

## Usage

### Basic Lookup

```php
use Bkhim\Geolocation\Geolocation;

$details = Geolocation::lookup('8.8.8.8');

echo $details->getIp();          // 8.8.8.8
echo $details->getCity();        // Mountain View
echo $details->getRegion();      // California
echo $details->getCountry();     // United States
echo $details->getCountryCode(); // US
echo $details->getLatitude();    // 37.386
echo $details->getLongitude();   // -122.0838

// Get as array
$data = $details->toArray();
```

### Specific Driver Usage

```php
// Use specific driver
$ipinfoDetails = Geolocation::driver('ipinfo')->lookup('8.8.8.8');
$maxmindDetails = Geolocation::driver('maxmind')->lookup('8.8.8.8');

// Switch default driver temporarily
config(['geolocation.drivers.default' => 'maxmind']);
$details = Geolocation::lookup('8.8.8.8');
```

### Error Handling

```php
try {
    $details = Geolocation::lookup('8.8.8.8');
} catch (\Bkhim\Geolocation\GeolocationException $e) {
    // Handle errors (invalid IP, API failures, etc.)
    logger()->error('Geolocation failed: ' . $e->getMessage());
}
```

## Advanced Configuration

### Custom Cache Store

```php
// config/geolocation.php
'cache' => [
    'enabled' => true,
    'ttl' => 86400,
    'store' => 'redis', // Use specific cache store
],
```

### Custom HTTP Client Options

```php
// config/geolocation.php
'ipinfo' => [
    'driver' => 'ipinfo',
    'access_token' => env('GEOLOCATION_IPINFO_ACCESS_TOKEN'),
    'client_options' => [
        'timeout' => 10,
        'connect_timeout' => 5,
        'headers' => [
            'User-Agent' => 'Your-App-Name/1.0',
        ],
    ],
],
```

## Translation Support

The package includes translations for country names. Publish translation files:

```bash
php artisan vendor:publish --tag="geolocation-translations"
```

### Using Translations

```php
app()->setLocale('pt');
$details = Geolocation::lookup('8.8.8.8');
echo $details->getCountry(); // "Estados Unidos" instead of "United States"
```

## Troubleshooting

### MaxMind Database Issues

```bash
# Check file permissions
chmod 644 /path/to/GeoLite2-City.mmdb

# Use absolute path in .env
MAXMIND_DATABASE_PATH="/absolute/path/to/GeoLite2-City.mmdb"
```

### Common Errors

- **Invalid IP address**: Ensure IP validation passes `filter_var($ip, FILTER_VALIDATE_IP)`
- **API rate limits**: Implement caching or use MaxMind for heavy usage
- **Database not found**: Verify MaxMind database path and permissions

## Testing

```bash
# Run tests
composer test

```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## Changelog

### v2.1.0
- Complete rewrite with multiple driver support
- Laravel 12+ compatibility
- Enhanced error handling and validation
- MaxMind database integration
- Improved caching system
- Better testing infrastructure

## License

This package is open-source software licensed under the MIT License.

## Credits

- **Original Author**: [Adriano Rosa](https://github.com/adrianorsouza)
- **Current Maintainer**: [Brian Kimathi (Blancos Khim)](https://github.com/bkhim)
- **Contributors**: [List of contributors](https://github.com/bkhim/laravel-geolocation/graphs/contributors)

## Support

- [GitHub Issues](https://github.com/bkhim/laravel-geolocation/issues)
- [Documentation](https://github.com/bkhim/laravel-geolocation/wiki)
- [Packagist](https://packagist.org/packages/bkhim/laravel-geolocation)

---

**Note**: This package is actively maintained. For bug reports, feature requests, or contributions, please use the GitHub issue tracker.
