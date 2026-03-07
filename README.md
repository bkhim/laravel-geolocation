# Laravel Geolocation Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bkhim/laravel-geolocation.svg)](https://packagist.org/packages/bkhim/laravel-geolocation)
[![License](https://img.shields.io/packagist/l/bkhim/laravel-geolocation.svg)](LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/bkhim/laravel-geolocation.svg)](https://php.net)

A modern, comprehensive IP geolocation package for Laravel with support for multiple providers (IpInfo, MaxMind, IPStack, IPGeolocation, ipapi.co). Get accurate visitor location data including city, country, timezone, currency, ISP information, and security detection. Perfect for user personalization, analytics, fraud prevention, and geo-targeting.

> **Release**: v4.0.6 — Stable (2025-12-12). This is a major release with architecture improvements, new drivers, and expanded geolocation features. See [CHANGELOG.md](CHANGELOG.md) for details.

## Table of Contents

- [Features](#features)
- [Use Cases](#use-cases)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [API Reference](#api-reference)
- [Advanced Features](#advanced-features)
- [Troubleshooting](#troubleshooting)
- [Testing](#testing)
- [Contributing](#contributing)
- [License & Support](#license--support)

## Features

- **5 Geolocation Providers**: IpInfo.io, MaxMind GeoIP2, IPStack.com, IPGeolocation.io, and ipapi.co for reliable IP address location lookup
- **Laravel 10+ Compatible**: Full support for Laravel 10.x through 12.x with modern PHP 8.1+ features
- **Free & Premium Options**: From completely free IP geolocation to enterprise-grade location services with advanced features
- **Rich Location Data**: City, region, country, GPS coordinates, timezone, postal codes, currency, and ISP/organization information
- **Advanced Caching System**: High-performance caching with configurable TTL and provider-specific cache keys for optimal speed
- **Production-Ready Error Handling**: Comprehensive exception management and IP validation for IPv4 and IPv6 addresses
- **Multi-language Support**: Country name translations and provider-specific language options for international applications
- **Security Detection**: Proxy, VPN, Tor, crawler, and mobile device detection for fraud prevention and analytics
- **Developer-Friendly**: Flexible configuration, Artisan commands, and extensive documentation for rapid integration
- **Modular Addon Architecture**: GDPR consent management, IP anonymization, rate limiting, and middleware support
- **Translation Support**: Country names available in multiple languages

## Use Cases

This package is perfect for:

- **User Personalization & Localization**: Currency detection, language localization, timezone handling, regional content
- **Security & Fraud Prevention**: Proxy/VPN detection, Tor detection, geoblocking, risk assessment
- **Analytics & Business Intelligence**: Visitor analytics, market analysis, performance monitoring, A/B testing
- **E-commerce & Marketing**: Geo-targeting, shipping optimization, tax calculation, compliance
- **Mobile & Device Detection**: Mobile optimization, bot detection, device targeting

## Requirements

- PHP 8.1 or higher
- Laravel 10.x, 11.x, or 12.x
- Composer
- For MaxMind provider: MaxMind GeoLite2 database (free) or GeoIP2 database (paid)

## Installation

Install the package via Composer:

```bash
composer require bkhim/laravel-geolocation
```

The package uses Laravel's auto-discovery to register the service provider and facades. For manual registration (optional):

**For Laravel 10 and earlier**, add to `config/app.php`:

```php
'providers' => [
    Bkhim\Geolocation\GeolocationServiceProvider::class,
],

'aliases' => [
    'Geolocation' => Bkhim\Geolocation\Geolocation::class,
    'IpAnonymizer' => Bkhim\Geolocation\Facades\IpAnonymizer::class,
    'LocationConsentManager' => Bkhim\Geolocation\Facades\LocationConsentManager::class,
],
```

**For Laravel 11+**, add the service provider to `bootstrap/providers.php`:

```php
return [
    // Other service providers...
    Bkhim\Geolocation\GeolocationServiceProvider::class,
];
```

And add aliases to `config/app.php`:

```php
'aliases' => [
    'Geolocation' => Bkhim\Geolocation\Geolocation::class,
    'IpAnonymizer' => Bkhim\Geolocation\Facades\IpAnonymizer::class,
    'LocationConsentManager' => Bkhim\Geolocation\Facades\LocationConsentManager::class,
],
```

### Publish Configuration

Publish the configuration file to customize your geolocation settings:

```bash
php artisan vendor:publish --provider="Bkhim\Geolocation\GeolocationServiceProvider"
```

This will create `config/geolocation.php` where you can configure providers, caching, and addons.

## Configuration

After publishing the configuration, set your environment variables in `.env`. See `.env.example` for all available options.

### Environment Variables

```env
# Default driver (ipapi, ipinfo, ipstack, ipgeolocation, maxmind)
GEOLOCATION_DRIVER=ipapi

# Request & Cache Configuration
GEOLOCATION_TIMEOUT=5
GEOLOCATION_CACHE_ENABLED=true
GEOLOCATION_CACHE_TTL=86400
GEOLOCATION_RETRY_ATTEMPTS=2
GEOLOCATION_RETRY_DELAY=100

# IpInfo Configuration
GEOLOCATION_IPINFO_ACCESS_TOKEN=your_token_here

# IPStack Configuration
GEOLOCATION_IPSTACK_ACCESS_KEY=your_api_key_here
IPSTACK_SECURE=true

# IPGeolocation Configuration
GEOLOCATION_IPGEOLOCATION_API_KEY=your_api_key_here
IPGEOLOCATION_LANGUAGE=en
IPGEOLOCATION_INCLUDE_HOSTNAME=false
IPGEOLOCATION_INCLUDE_SECURITY=false
IPGEOLOCATION_INCLUDE_USERAGENT=false

# MaxMind Configuration
MAXMIND_DATABASE_PATH=/path/to/GeoLite2-City.mmdb
MAXMIND_LICENSE_KEY=your_license_key

# Addon Configuration
GEOLOCATION_MIDDLEWARE_ENABLED=false
GEOLOCATION_RATE_LIMITING_ENABLED=false
GEOLOCATION_ANONYMIZATION_ENABLED=false
GEOLOCATION_GDPR_ENABLED=false
```

### Provider Selection

Choose the driver that fits your needs:

| Provider | Free Tier | API Key Required | HTTPS | Special Features |
|----------|-----------|------------------|-------|------------------|
| **ipapi.co** | ✅ 30K/month | ❌ No | ✅ Yes | No API key needed, IPv4/IPv6 |
| **IpInfo** | ✅ 50K/month | ✅ Yes | ✅ Yes | Popular, reliable, generous free tier |
| **IPStack** | ✅ 10K/month | ✅ Yes | 💰 Paid only | Multiple tiers, comprehensive data |
| **IPGeolocation** | ✅ 1K/month | ✅ Yes | ✅ Yes | 12 languages, security features |
| **MaxMind** | ✅ Local DB | ❌ No | N/A | Privacy-focused, fastest, offline |

**Recommendation by Use Case**:
- **Getting Started**: Use **ipapi.co** (no setup required)
- **Production Apps**: Use **IpInfo** (reliable with generous limits)
- **High Volume**: Use **MaxMind** (local database, no API limits)
- **Advanced Features**: Use **IPGeolocation** (security data, translations)
- **Enterprise**: Use **IPStack** (comprehensive data, support)

## Usage

### Basic Lookup

```php
use Bkhim\Geolocation\Geolocation;

// Detect visitor location automatically
$details = Geolocation::lookup();

// Lookup specific IP address
$details = Geolocation::lookup('8.8.8.8');

// Access location data
echo $details->getCity();           // Mountain View
echo $details->getCountry();        // United States
echo $details->getCountryCode();    // US
echo $details->getLatitude();       // 37.386
echo $details->getLongitude();      // -122.0838
echo $details->getTimezone();       // America/Los_Angeles
echo $details->getCurrencyCode();   // USD
```

### Specific Driver Usage

```php
// Use specific drivers
$ipapiDetails = Geolocation::driver('ipapi')->lookup('8.8.8.8');          // Free
$ipinfoDetails = Geolocation::driver('ipinfo')->lookup('8.8.8.8');        // Popular
$ipstackDetails = Geolocation::driver('ipstack')->lookup('8.8.8.8');      // Feature-rich
$ipgeoDetails = Geolocation::driver('ipgeolocation')->lookup('8.8.8.8');  // Advanced
$maxmindDetails = Geolocation::driver('maxmind')->lookup('8.8.8.8');      // Local database
```

### Display User Location with Flag

```php
$details = Geolocation::lookup();

echo $details->getCountryFlag() . ' ' . $details->getFormattedAddress();
// Output: 🇺🇸 Mountain View, CA, United States

echo "Welcome visitor from " . $details->getShortAddress() . "!";
// Output: Welcome visitor from Mountain View, US!
```

### Create Map Links

```php
$details = Geolocation::lookup('8.8.8.8');

// Google Maps
$googleUrl = $details->getGoogleMapsLink();
echo "<a href='{$googleUrl}' target='_blank'>View on Google Maps</a>";

// Apple Maps (for mobile Safari)
$appleUrl = $details->getAppleMapsLink();
echo "<a href='{$appleUrl}'>Open in Apple Maps</a>";

// OpenStreetMap
$osmUrl = $details->getOpenStreetMapLink();
echo "<a href='{$osmUrl}' target='_blank'>View on OpenStreetMap</a>";
```

### Security & Network Analysis

```php
$details = Geolocation::lookup($userIP);

if ($details->isProxy()) {
    echo "⚠️ Proxy/VPN detected from " . $details->getIsp();
}

if ($details->isTor()) {
    echo "🔒 Tor exit node detected";
}

if ($details->isCrawler()) {
    echo "🤖 Bot/crawler detected: " . $details->getHostname();
}

echo "ISP: " . $details->getIsp();
echo "ASN: " . $details->getAsn() . " (" . $details->getAsnName() . ")";
echo "Connection: " . $details->getConnectionType();
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

### Artisan Commands

The package includes several Artisan commands:

```bash
# Test geolocation lookup
php artisan geolocation:lookup [ip]

# Clear geolocation cache
php artisan geolocation:cache:clear
```

## API Reference

### GeolocationDetails Methods

The `GeolocationDetails` object returned by `Geolocation::lookup()` provides the following methods:

#### Basic Location Information
- `getIp()` - IP address
- `getCity()` - City name
- `getRegion()` - State/Province name
- `getCountry()` - Country name (translated if available)
- `getCountryCode()` - ISO country code (e.g., 'US', 'GB')

#### Coordinates
- `getLatitude()` - Latitude coordinate
- `getLongitude()` - Longitude coordinate

#### Time & Timezone
- `getTimezone()` - Timezone identifier (e.g., 'America/New_York')
- `getTimezoneOffset()` - Hours offset from UTC (e.g., -5, +2)

#### Currency Information
- `getCurrency()` - Currency name (e.g., 'US Dollar')
- `getCurrencyCode()` - Currency code (e.g., 'USD')
- `getCurrencySymbol()` - Currency symbol (e.g., '$')

#### Geographic Regions
- `getContinent()` - Continent name (e.g., 'North America')
- `getContinentCode()` - Continent code (e.g., 'NA')

#### Additional Data
- `getPostalCode()` - Postal/ZIP code
- `getOrg()` - Organization/ISP name

#### Network & ISP Information
- `getIsp()` - Internet Service Provider name
- `getAsn()` - Autonomous System Number (e.g., 'AS15169')
- `getAsnName()` - ASN organization name
- `getConnectionType()` - Connection type (e.g., 'corporate', 'residential')
- `getHostname()` - Hostname/reverse DNS

#### Security & Device Detection
- `isMobile()` - Is mobile connection
- `isProxy()` - Is proxy/VPN
- `isCrawler()` - Is web crawler/bot
- `isTor()` - Is Tor exit node

#### Utility Methods
- `getFormattedAddress()` - "Mountain View, CA, United States"
- `getShortAddress()` - "Mountain View, US"
- `getFullAddress()` - "Mountain View, CA 94043, US"
- `getGoogleMapsLink()` - Google Maps link with coordinates
- `getCountryFlag()` - Country flag emoji (🇺🇸)
- `getCountryFlagUrl()` - URL to flag image with customizable width
- `toArray()` - All data as associative array
- `jsonSerialize()` - JSON representation

## Advanced Features

### Caching

The package includes a high-performance caching system. Configure cache settings in `config/geolocation.php`:

```php
'cache' => [
    'enabled' => true,
    'ttl' => 86400, // seconds
    'store' => 'redis', // optional, uses default cache store
],
```

### Fallback Configuration

Enable fallback to another provider if the primary fails:

```php
'fallback' => [
    'enabled' => true,
    'order' => ['ipinfo', 'maxmind'],
    'max_attempts' => 2,
],
```

### Logging

Log geolocation requests for debugging:

```php
'logging' => [
    'enabled' => true,
    'level_success' => 'info',
    'level_error' => 'error',
],
```

### Custom HTTP Client Options

You can customize HTTP client options per provider in the configuration:

```php
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

### Translation Support

Country names can be translated. Publish translation files:

```bash
php artisan vendor:publish --tag="geolocation-translations"
```

Then set your application locale:

```php
app()->setLocale('pt');
$details = Geolocation::lookup('8.8.8.8');
echo $details->getCountry(); // "Estados Unidos" instead of "United States"
```

### IP Anonymization (GDPR/Privacy)

Anonymize IP addresses for privacy and GDPR compliance:

```php
use IpAnonymizer;

$anonIp = IpAnonymizer::anonymize($ipAddress);
```

Enable the anonymization addon in your `.env`:

```env
GEOLOCATION_ANONYMIZATION_ENABLED=true
```

### GDPR Consent Management

Manage GDPR consent for location tracking:

```php
use LocationConsentManager;

// Check if consent is needed for an IP
$needsConsent = LocationConsentManager::needsConsent($ipAddress);

// Check if user has given consent
$hasConsent = LocationConsentManager::hasGivenConsent();

// Give consent (set cookie)
LocationConsentManager::giveConsent();

// Withdraw consent (remove cookie)
LocationConsentManager::withdrawConsent();
```

Enable the GDPR addon in your `.env`:

```env
GEOLOCATION_GDPR_ENABLED=true
```

### Middleware & Rate Limiting

The package includes middleware for geo-based access control and rate limiting. Enable in configuration:

```env
GEOLOCATION_MIDDLEWARE_ENABLED=true
GEOLOCATION_RATE_LIMITING_ENABLED=true
```

## Troubleshooting

### Provider-Specific Issues

#### IPStack
- **HTTPS Error**: Free tier only supports HTTP. Upgrade to paid plan for HTTPS
- **Rate Limit**: Free tier limited to 10,000 requests/month

#### IPGeolocation
- **Language Support**: Multi-language responses require paid plans
- **Security Features**: Advanced features (hostname, security) require appropriate plan tiers

#### IpInfo
- **Rate Limit**: Free tier limited to 50,000 requests/month
- **API Token**: Ensure your token is valid and active

#### ipapi.co
- **No Configuration**: This provider requires no setup - if it's not working, check your internet connection
- **Rate Limits**: May apply for extremely high volume usage

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
- **API key errors**: Double-check your environment variables and API key validity

## Testing

Run the test suite with:

```bash
composer test
```

The package includes comprehensive Pest tests covering all providers and features.

## Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## License & Support

This package is open-source software licensed under the MIT License.

- **GitHub Issues**: [https://github.com/bkhim/laravel-geolocation/issues](https://github.com/bkhim/laravel-geolocation/issues)
- **Documentation**: [https://briankimathi.com/packages/laravel-geolocation](https://briankimathi.com/packages/laravel-geolocation)
- **Packagist**: [https://packagist.org/packages/bkhim/laravel-geolocation](https://packagist.org/packages/bkhim/laravel-geolocation)

---

**Note**: This package is actively maintained. For bug reports, feature requests, or contributions, please use the GitHub issue tracker.